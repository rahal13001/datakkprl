import json

with open('/home/rahal/.gemini/antigravity-cli/brain/5ec8b650-2d18-42e5-a17b-b1b42872d739/.system_generated/logs/transcript_full.jsonl', 'r') as f:
    for line in f:
        data = json.loads(line)
        if data.get('step_index') == 152:
            calls = data.get('tool_calls', [])
            for call in calls:
                if call['name'] == 'write_to_file':
                    content = call['args']['CodeContent']
                    with open('resources/views/livewire/kkprl-proposal-wizard.blade.php', 'w', encoding='utf-8') as out:
                        out.write(content)
                    print("Extracted base file from transcript step 152!")
                    exit(0)
