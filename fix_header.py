import re

with open('resources/views/livewire/kkprl-proposal-wizard.blade.php', 'r', encoding='utf-8') as f:
    text = f.read()

leaflet_css = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>\n'
leaflet_js = '    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>\n'

text = text.replace(leaflet_css, '')
text = text.replace(leaflet_js, '')

text = text.replace('<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>', '<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>\n    ' + leaflet_css + leaflet_js)

with open('resources/views/livewire/kkprl-proposal-wizard.blade.php', 'w', encoding='utf-8') as f:
    f.write(text)

