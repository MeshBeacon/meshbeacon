import os
import re

replacements = {
    r'\bbg-gray-900\b': 'bg-gray-50 dark:bg-gray-900',
    r'\bbg-gray-800/50\b': 'bg-white dark:bg-gray-800/50',
    r'\bbg-gray-800/75\b': 'bg-white dark:bg-gray-800/75',
    r'\bbg-gray-800\b': 'bg-white dark:bg-gray-800',
    r'\bbg-gray-700\b': 'bg-gray-100 dark:bg-gray-700',
    r'\bbg-gray-950/50\b': 'bg-gray-200 dark:bg-gray-950/50',
    r'\bbg-white/5\b': 'bg-gray-100 dark:bg-white/5',
    r'\bbg-white/10\b': 'bg-gray-200 dark:bg-white/10',
    r'\btext-white\b': 'text-gray-900 dark:text-white',
    r'\btext-gray-300\b': 'text-gray-600 dark:text-gray-300',
    r'\btext-gray-400\b': 'text-gray-500 dark:text-gray-400',
    r'\bborder-white/10\b': 'border-gray-200 dark:border-white/10',
    r'\boutline-white/10\b': 'outline-gray-200 dark:outline-white/10',
    r'\bring-white/10\b': 'ring-gray-200 dark:ring-white/10',
    r'\bring-white/15\b': 'ring-gray-200 dark:ring-white/15',
    r'\bdivide-white/15\b': 'divide-gray-200 dark:divide-white/15',
    r'\bhover:bg-white/5\b': 'hover:bg-gray-200 dark:hover:bg-white/5',
    r'\bhover:bg-white/10\b': 'hover:bg-gray-200 dark:hover:bg-white/10',
    r'\bhover:text-white\b': 'hover:text-gray-900 dark:hover:text-white',
    r'\bhover:text-gray-300\b': 'hover:text-gray-700 dark:hover:text-gray-300',
}

def process_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    original = content
    # To prevent replacing something that's already replaced (e.g. if we run this twice)
    # We should be careful, but since we are doing this once, it's fine.
    
    # We also need to avoid touching 'dark:bg-gray-900' by ignoring matches that are preceded by 'dark:'
    for pattern, replacement in replacements.items():
        # Negative lookbehind to ensure no 'dark:' or 'light:' precedes it
        content = re.sub(r'(?<!dark:)' + pattern, replacement, content)

    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated {filepath}")

for root, _, files in os.walk('resources/views'):
    for file in files:
        if file.endswith('.blade.php'):
            process_file(os.path.join(root, file))
