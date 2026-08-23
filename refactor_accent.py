import os
import re

replacements = {
    r'\btext-yellow-400\b': 'text-orange-600 dark:text-orange-400',
    r'\btext-yellow-500\b': 'text-orange-600 dark:text-orange-500',
    r'\btext-yellow-300\b': 'text-orange-500 dark:text-orange-300',
    r'\bhover:text-yellow-400\b': 'hover:text-orange-700 dark:hover:text-orange-400',
    r'\bhover:text-yellow-300\b': 'hover:text-orange-500 dark:hover:text-orange-300',
    r'\bgroup-hover:text-yellow-400\b': 'group-hover:text-orange-600 dark:group-hover:text-orange-400',
    
    r'\bbg-yellow-500\b': 'bg-orange-500',
    r'\bbg-yellow-400\b': 'bg-orange-400',
    r'\bbg-yellow-800/60\b': 'bg-orange-800/60',
    r'\bhover:bg-yellow-400\b': 'hover:bg-orange-600 dark:hover:bg-orange-400',
    r'\bhover:bg-yellow-500\b': 'hover:bg-orange-600 dark:hover:bg-orange-500',
    r'\bfocus:bg-yellow-500\b': 'focus:bg-orange-500',
    
    r'\bring-yellow-500\b': 'ring-orange-500',
    r'\bring-yellow-500/50\b': 'ring-orange-500/50',
    
    r'\bfocus:outline-yellow-500\b': 'focus:outline-orange-500',
    r'\bfocus-visible:outline-yellow-500\b': 'focus-visible:outline-orange-500',
    r'\bfocus-visible:ring-yellow-500\b': 'focus-visible:ring-orange-500',
}

def process_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    original = content
    
    for pattern, replacement in replacements.items():
        # Negative lookbehind to ensure no 'dark:' or 'light:' precedes it
        content = re.sub(r'(?<!dark:)(?<!light:)' + pattern, replacement, content)

    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated {filepath}")

for root, _, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.blade.php'):
            process_file(os.path.join(root, file))
