import re

with open('resources/views/livewire/kkprl-proposal-wizard.blade.php', 'r', encoding='utf-8') as f:
    text = f.read()

# Remove Livewire specific injection tags that break rendering when saved as raw blade
text = re.sub(r'<!--\[if BLOCK\]><!\[endif\]-->', '', text)
text = re.sub(r'<\?php\s*if\(\\Livewire\\Mechanisms\\ExtendBlade\\ExtendBlade::isRenderingLivewireComponent\(\)\):\s*\?>.*?(?:<\?php\s*endif;\s*\?>|$)', '', text, flags=re.DOTALL)

# Blade echo
text = re.sub(r'<\?php\s+echo\s+e\((.*?)\);\s+\?>', r'{{ \1 }}', text)
text = re.sub(r'<\?php\s+echo\s+(.*?);\s+\?>', r'{!! \1 !!}', text)

# Blade if/else
text = re.sub(r'<\?php\s+if\s*\((.*?)\):\s+\?>', r'@if(\1)', text)
text = re.sub(r'<\?php\s+elseif\s*\((.*?)\):\s+\?>', r'@elseif(\1)', text)
text = re.sub(r'<\?php\s+else:\s+\?>', r'@else', text)
text = re.sub(r'<\?php\s+endif;\s+\?>', r'@endif', text)

# Blade foreach
text = re.sub(r'<\?php\s+foreach\s*\((.*?)\):\s+\?>', r'@foreach(\1)', text)
text = re.sub(r'<\?php\s+endforeach;\s+\$__env->popLoop\(\);\s+\$loop\s+=\s+\$__env->getLastLoop\(\);\s+\?>', r'@endforeach', text)
text = re.sub(r'<\?php\s+endforeach;\s+\$__env->popLoop\(\);\s+.*?\?>', r'@endforeach', text)

# Blade error
text = re.sub(r'<\?php\s+\$__errorArgs\s*=\s*\[\'(.*?)\'\];.*?if\s*\(\$__messageOriginal\):\s+\?>', r'@error(\'\1\')', text, flags=re.DOTALL)
text = re.sub(r'<\?php\s+unset\(\$__errorArgs,\s+\$__bag\);\s+\?>', r'', text)

# Blade unless
text = re.sub(r'<\?php\s+if\s*\(!\s*\((.*?)\)\):\s+\?>', r'@unless(\1)', text)

# Blade php
text = re.sub(r'<\?php\s*\((.*?)\);\s*\?>', r'@php(\1)', text)
text = re.sub(r'<\?php\s+(.*?);\s*\?>', r'<?php \1; ?>', text) # Safest way to restore PHP tags without @php() syntax errors

# Clean up path trailer
text = re.sub(r'<\?php\s+/\*\*PATH.*?\?>', '', text)

with open('resources/views/livewire/kkprl-proposal-wizard.blade.php', 'w', encoding='utf-8') as f:
    f.write(text.strip())

print("Cleaned!")
