<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            canvas: null,
            ctx: null,
            isDrawing: false,
            hasSignature: false,
            lastX: 0,
            lastY: 0,

            init() {
                this.canvas = this.$refs.canvas;
                this.ctx = this.canvas.getContext('2d');
                this.ctx.strokeStyle = '{{ $getPenColor() }}';
                this.ctx.lineWidth = {{ $getPenWidth() }};
                this.ctx.lineCap = 'round';
                this.ctx.lineJoin = 'round';

                // Fill with background color
                this.ctx.fillStyle = '{{ $getBackgroundColor() }}';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);

                // Load existing signature if available
                const existingValue = $wire.get('{{ $getStatePath() }}');
                if (existingValue && existingValue.startsWith('data:image')) {
                    const img = new Image();
                    img.onload = () => {
                        this.ctx.drawImage(img, 0, 0);
                        this.hasSignature = true;
                    };
                    img.src = existingValue;
                }
            },

            getPos(e) {
                const rect = this.canvas.getBoundingClientRect();
                const scaleX = this.canvas.width / rect.width;
                const scaleY = this.canvas.height / rect.height;

                if (e.touches) {
                    return {
                        x: (e.touches[0].clientX - rect.left) * scaleX,
                        y: (e.touches[0].clientY - rect.top) * scaleY
                    };
                }
                return {
                    x: (e.clientX - rect.left) * scaleX,
                    y: (e.clientY - rect.top) * scaleY
                };
            },

            startDrawing(e) {
                e.preventDefault();
                this.isDrawing = true;
                const pos = this.getPos(e);
                this.lastX = pos.x;
                this.lastY = pos.y;
                this.ctx.beginPath();
                this.ctx.moveTo(pos.x, pos.y);
            },

            draw(e) {
                if (!this.isDrawing) return;
                e.preventDefault();
                const pos = this.getPos(e);
                this.ctx.lineTo(pos.x, pos.y);
                this.ctx.stroke();
                this.lastX = pos.x;
                this.lastY = pos.y;
            },

            stopDrawing() {
                if (this.isDrawing) {
                    this.isDrawing = false;
                    this.hasSignature = true;
                    this.save();
                }
            },

            save() {
                const dataUrl = this.canvas.toDataURL('image/png');
                $wire.set('{{ $getStatePath() }}', dataUrl);
            },

            clear() {
                this.ctx.fillStyle = '{{ $getBackgroundColor() }}';
                this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
                this.ctx.strokeStyle = '{{ $getPenColor() }}';
                this.hasSignature = false;
                $wire.set('{{ $getStatePath() }}', null);
            }
        }"
        class="w-full"
    >
        <div class="relative border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800"
             style="max-width: 100%; width: {{ $getCanvasWidth() }}px;">
            <canvas
                x-ref="canvas"
                width="{{ $getCanvasWidth() }}"
                height="{{ $getCanvasHeight() }}"
                class="cursor-crosshair touch-none"
                style="width: 100%; aspect-ratio: {{ $getCanvasWidth() }}/{{ $getCanvasHeight() }};"
                @mousedown="startDrawing($event)"
                @mousemove="draw($event)"
                @mouseup="stopDrawing()"
                @mouseleave="stopDrawing()"
                @touchstart="startDrawing($event)"
                @touchmove="draw($event)"
                @touchend="stopDrawing()"
            ></canvas>

            {{-- Placeholder text when empty --}}
            <div
                x-show="!hasSignature"
                class="absolute inset-0 flex items-center justify-center pointer-events-none"
            >
                <span class="text-gray-400 dark:text-gray-500 text-sm italic">Tanda tangan di sini</span>
            </div>
        </div>

        {{-- Clear button --}}
        <div style="margin-top: 4px;">
            <button
                type="button"
                @click="clear()"
                x-show="hasSignature"
                style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; font-size: 11px; color: #ef4444; background: transparent; border: none; cursor: pointer; border-radius: 4px;"
                onmouseover="this.style.background='rgba(239,68,68,0.1)'"
                onmouseout="this.style.background='transparent'"
            >
                <svg style="width: 12px; height: 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Hapus
            </button>
        </div>
    </div>
</x-dynamic-component>
