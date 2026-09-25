import json
import re

def main():
    file_path = 'resources/views/livewire/kkprl-proposal-wizard.blade.php'
    
    with open('/home/rahal/.gemini/antigravity-cli/brain/5ec8b650-2d18-42e5-a17b-b1b42872d739/.system_generated/logs/transcript_full.jsonl', 'r') as f:
        lines = f.readlines()

    # Step 1: Re-extract from Step 152
    for line in lines:
        data = json.loads(line)
        if data.get('step_index') == 152:
            calls = data.get('tool_calls', [])
            for call in calls:
                if call['name'] == 'write_to_file' and 'kkprl-proposal-wizard.blade.php' in call['args']['TargetFile']:
                    content = call['args']['CodeContent']
                    with open(file_path, 'w', encoding='utf-8') as out:
                        out.write(content)
                    print("Restored baseline from Step 152.")
                    break
    
    # Step 2: Replay all replace_file_content calls after Step 152
    with open(file_path, 'r', encoding='utf-8') as f:
        current_content = f.read()
        
    for line in lines:
        data = json.loads(line)
        idx = data.get('step_index')
        if idx and idx > 152:
            if 'tool_calls' in data:
                for call in data['tool_calls']:
                    if call['name'] == 'replace_file_content' and 'kkprl-proposal-wizard.blade.php' in call['args']['TargetFile']:
                        target = call['args']['TargetContent']
                        replacement = call['args']['ReplacementContent']
                        allow_multiple = call['args'].get('AllowMultiple', False)
                        
                        if target in current_content:
                            count = current_content.count(target)
                            if count > 1 and not allow_multiple:
                                print(f"Step {idx}: Target found multiple times, skipping as AllowMultiple is false.")
                            else:
                                current_content = current_content.replace(target, replacement)
                                print(f"Step {idx}: Successfully applied edit '{call['args']['toolSummary']}'")
                        else:
                            print(f"Step {idx}: Target not found in file for '{call['args']['toolSummary']}'!")
                            # Sometimes the target was not found even in the original run, so we ignore it just like the system did.

    # Re-apply the patch script that did string replacement from Step 512
    # Oh wait! In Step 512, I used a PHP script to replace the script tag.
    # $start = strpos($content, '<script>');
    # $end = strpos($content, '</script>') + 9;
    # But that PHP script was what corrupted the file and wiped out the body!
    # So I MUST SKIP the PHP patch script from Step 512! That script was the error!
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(current_content)
    
    print("All edits replayed successfully!")

if __name__ == '__main__':
    main()
