import os
import re

css_file = "/Users/vitalii/Sites/tos/assets/style.css"
out_dir = "/Users/vitalii/Sites/tos/assets/scss"

if not os.path.exists(out_dir):
    os.makedirs(out_dir)

with open(css_file, "r", encoding="utf-8") as f:
    content = f.read()

pattern = re.compile(r'/\* =========================================\s+(.*?)\s+========================================= \*/', re.DOTALL)
matches = list(pattern.finditer(content))

if not matches:
    print("No sections found!")
    exit(1)

filename_map = {
    "0": "_helpers.scss", "1": "_variables.scss", "2": "_layout.scss",
    "3": "_navigation.scss", "4": "_components.scss", "5": "_forms.scss",
    "6": "_cards.scss", "7": "_images.scss", "8": "_dashboard.scss",
    "9": "_lightbox.scss", "10": "_responsive.scss", "11": "_account.scss",
    "12": "_headers.scss", "13": "_misc.scss"
}

pre_content = content[:matches[0].start()].strip()
files_created = []

for i in range(len(matches)):
    start_pos = matches[i].start()
    end_pos = matches[i+1].start() if i + 1 < len(matches) else len(content)
    section_text = content[start_pos:end_pos].strip()
    
    title_match = matches[i].group(1)
    num_match = re.search(r'^(\d+)\.', title_match.strip())
    
    if num_match:
        num = num_match.group(1)
        filename = filename_map.get(num, f"_section_{num}.scss")
    else:
        filename = f"_section_unknown_{i}.scss"
        
    filepath = os.path.join(out_dir, filename)
    mode = "a" if os.path.exists(filepath) else "w"
    with open(filepath, mode, encoding="utf-8") as f:
        if mode == "a": f.write("\n\n")
        f.write(section_text + "\n")
    
    if filename not in files_created:
        files_created.append(filename)

if pre_content:
    with open(os.path.join(out_dir, "_base.scss"), "w", encoding="utf-8") as f:
        f.write(pre_content + "\n")
    files_created.insert(0, "_base.scss")

with open(os.path.join(out_dir, "style.scss"), "w", encoding="utf-8") as f:
    f.write("// Main SCSS File\n")
    for fname in files_created:
        name = fname.replace(".scss", "").replace("_", "", 1)
        f.write(f'@use "{name}";\n')

print(f"Split {len(matches)} sections into {len(files_created)} SCSS files.")
