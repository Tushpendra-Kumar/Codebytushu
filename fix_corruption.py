import os
import glob

# Find all PHP files
files = glob.glob('E:/Codebytushu/**/*.php', recursive=True)
count = 0

for filepath in files:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Look for the exact corruption marker
    idx = content.find("<?php require_once <?php")
    
    if idx != -1:
        print(f"Fixing {filepath}...")
        
        # We need to figure out the depth to require ai-widget-loader.php properly
        # Count the slashes in the path relative to public_html root
        rel_path = os.path.relpath(filepath, 'E:/Codebytushu')
        depth = rel_path.count(os.sep)
        
        # Depth 0 (root): __DIR__ . '/includes/ai-widget-loader.php'
        # Depth 1 (Leetcode/): __DIR__ . '/../includes/ai-widget-loader.php'
        # Depth 2 (store/checkout/): __DIR__ . '/../../includes/ai-widget-loader.php'
        if depth == 0:
            require_str = "__DIR__ . '/includes/ai-widget-loader.php'"
        elif depth == 1:
            require_str = "__DIR__ . '/../includes/ai-widget-loader.php'"
        elif depth == 2:
            require_str = "__DIR__ . '/../../includes/ai-widget-loader.php'"
        else:
            prefix = '/..' * depth
            require_str = "__DIR__ . '" + prefix + "/includes/ai-widget-loader.php'"
            
        # The correct ending should just be the require statement and closing HTML
        correct_ending = f"<?php require_once {require_str}; ?>\n</body>\n</html>"
        
        # Truncate at the corruption point and append the correct ending
        new_content = content[:idx] + correct_ending
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        
        count += 1

print(f"Fixed {count} files successfully!")
