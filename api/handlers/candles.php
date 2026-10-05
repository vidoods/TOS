<?php
// api/handlers/candles.php — эндпоинт для получения свечей (OHLC)

function getCandles($pdo)
{
    try {
        $symbol   = strtoupper(trim($_GET['symbol'] ?? ''));
        $interval = $_GET['interval'] ?? '4h';
        $start    = isset($_GET['start']) && is_numeric($_GET['start']) ? (int)$_GET['start'] : 0;
        $end      = isset($_GET['end'])   && is_numeric($_GET['end'])   ? (int)$_GET['end']   : 0;

        if (empty($symbol)) {
            throw new Exception('Symbol is required.');
        }

        // ============================================================
        // КЛЮЧ TWELVE DATA (для форекса и металлов)
        // ============================================================
        $TWELVE_DATA_API_KEY = '5b6f62b2dfc64b229c8cd98dec64bc60';

        // ============================================================
        // Хелпер: вычисление интервала в секундах
        // ============================================================
        $intervalMap = [
            '1m' => 60, '5m' => 300, '15m' => 900, '30m' => 1800,
            '1h' => 3600, '2h' => 7200, '4h' => 14400, '1d' => 86400,
            '1w' => 604800, '1M' => 2592000,
        ];
        $intervalSec = $intervalMap[$interval] ?? 14400;

        // ============================================================
        // Маппинг индексов и фьючерсов на символы Yahoo Finance
        // ============================================================
        $yahooMapping = [
            'GER40' => '^GDAXI', 'GER30' => '^GDAXI', 'DE40' => '^GDAXI', 'DE30' => '^GDAXI', 'DAX' => '^GDAXI',
            'US30'  => '^DJI',   'DOW'   => '^DJI',   'DJI'  => '^DJI',
            'US100' => '^NDX',   'NAS100'=> '^NDX',   'NDX'  => '^NDX',
            'US500' => '^GSPC',  'SPX'   => '^GSPC',  'SPX500'=>'^GSPC',
            'UK100' => '^FTSE',  'FTSE'  => '^FTSE',
            'WTI'   => 'CL=F',   'OIL'   => 'CL=F',
            'BRENT' => 'BZ=F',
        ];

        $candles = [];

        // ============================================================
        // ИСТОЧНИК 1: Binance (крипта)
        // Пробуем только если символ не является индексом
        // ============================================================
        if (!isset($yahooMapping[$symbol])) {
            $binanceSymbol = $symbol;
            // Binance торгует к USDT, а не USD
            if (substr($binanceSymbol, -3) === 'USD' && substr($binanceSymbol, -4) !== 'USDT') {
                $binanceSymbol .= 'T';
            }

            $binanceIntervals = ['1m','3m','5m','15m','30m','1h','2h','4h','6h','8h','12h','1d','3d','1w','1M'];
            $binanceInterval  = in_array($interval, $binanceIntervals) ? $interval : '4h';

            $bUrl = "https://api.binance.com/api/v3/klines?symbol={$binanceSymbol}&interval={$binanceInterval}&limit=500";
            if ($start > 0 && $end > 0) {
                $bStart = ($start - $intervalSec * 30) * 1000;
                $bEnd   = ($end   + $intervalSec * 30) * 1000;
                $bUrl  .= "&startTime={$bStart}&endTime={$bEnd}";
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $bUrl,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $bResult   = curl_exec($ch);
            $bHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($bHttpCode === 200 && $bResult) {
                $bData = json_decode($bResult, true);
                if (is_array($bData) && !isset($bData['code'])) {
                    foreach ($bData as $k) {
                        $candles[] = [
                            'time'  => (int)($k[0] / 1000),
                            'open'  => (float)$k[1],
                            'high'  => (float)$k[2],
                            'low'   => (float)$k[3],
                            'close' => (float)$k[4],
                        ];
                    }
                }
            }
        }

        // ============================================================
        // ИСТОЧНИК 2: Yahoo Finance (индексы, фьючерсы)
        // ============================================================
        if (empty($candles) && isset($yahooMapping[$symbol])) {
            $ySymbol = $yahooMapping[$symbol];

            $yIntervalMap = [
                '1m' => '1m', '5m' => '5m', '15m' => '15m', '30m' => '30m',
                '1h' => '1h', '4h' => '1h',  // Yahoo не поддерживает 4h — берём 1h
                '1d' => '1d', '1w' => '1wk',  '1M' => '1mo',
            ];
            $yInterval = $yIntervalMap[$interval] ?? '1h';

            if ($start > 0 && $end > 0) {
                $period1 = $start - $intervalSec * 50;
                $period2 = $end   + $intervalSec * 50;
                $yUrl = "https://query2.finance.yahoo.com/v8/finance/chart/{$ySymbol}?interval={$yInterval}&period1={$period1}&period2={$period2}";
            } else {
                $yUrl = "https://query2.finance.yahoo.com/v8/finance/chart/{$ySymbol}?interval={$yInterval}&range=3mo";
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $yUrl,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 7,
            ]);
            $yResult = curl_exec($ch);
            curl_close($ch);

            $yData = json_decode($yResult, true);
            if (isset($yData['chart']['result'][0]['timestamp'])) {
                $timestamps = $yData['chart']['result'][0]['timestamp'];
                $q          = $yData['chart']['result'][0]['indicators']['quote'][0];
                for ($i = 0; $i < count($timestamps); $i++) {
                    if ($q['open'][$i] !== null && $q['close'][$i] !== null) {
                        $candles[] = [
                            'time'  => (int)$timestamps[$i],
                            'open'  => (float)$q['open'][$i],
                            'high'  => (float)$q['high'][$i],
                            'low'   => (float)$q['low'][$i],
                            'close' => (float)$q['close'][$i],
                        ];
                    }
                }
            }
        }

        // ============================================================
        // ИСТОЧНИК 3: Twelve Data (форекс, металлы — EUR/USD, XAU/USD)
        // ============================================================
        if (empty($candles)) {
            $tdSymbol = $symbol;
            // 6-символьные форекс пары: EURUSD -> EUR/USD
            if (strlen($tdSymbol) === 6 && ctype_alpha($tdSymbol)) {
                $tdSymbol = substr($tdSymbol, 0, 3) . '/' . substr($tdSymbol, 3);
            }

            $tdIntervalMap = [
                '1m' => '1min', '5m' => '5min', '15m' => '15min', '30m' => '30min',
                '1h' => '1h',   '2h' => '2h',   '4h'  => '4h',
                '1d' => '1day', '1w' => '1week', '1M' => '1month',
            ];
            $tdInterval = $tdIntervalMap[$interval] ?? '4h';

            $tdUrl = "https://api.twelvedata.com/time_series?symbol={$tdSymbol}&interval={$tdInterval}&apikey={$TWELVE_DATA_API_KEY}&outputsize=500";
            if ($start > 0 && $end > 0) {
                $startDate = urlencode(date('Y-m-d H:i:s', $start - $intervalSec * 30));
                $endDate   = urlencode(date('Y-m-d H:i:s', $end   + $intervalSec * 30));
                $tdUrl    .= "&start_date={$startDate}&end_date={$endDate}";
            }

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $tdUrl,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 7,
            ]);
            $tdResult = curl_exec($ch);
            curl_close($ch);

            $tdData = json_decode($tdResult, true);
            if (!isset($tdData['values'])) {
                $errMsg = $tdData['message'] ?? 'Symbol not found.';
                throw new Exception("Cannot load chart data: " . $errMsg);
            }

            // TwelveData возвращает от новых к старым — переворачиваем
            foreach (array_reverse($tdData['values']) as $val) {
                $candles[] = [
                    'time'  => strtotime($val['datetime']),
                    'open'  => (float)$val['open'],
                    'high'  => (float)$val['high'],
                    'low'   => (float)$val['low'],
                    'close' => (float)$val['close'],
                ];
            }
        }

        if (empty($candles)) {
            throw new Exception("No data found for symbol: {$symbol}");
        }

        echo json_encode(['success' => true, 'data' => $candles]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>