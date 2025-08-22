const generateRandom = () =>
	Math.random()
		.toString(36)
		.replace(/[^a-z]+/g, '')
		.substr(0, 10);

export default {
    name: 'drawio-preview',
    props: {
        filename: { type: String, default: null },
        fileid: { type: Number, default: null },
        isEmbedded: { type: Boolean, default: false },
    },
    render(createElement) {
        this.$emit('update:loaded', true);
        const rnd = generateRandom();
        const imgUrl = `/index.php/core/preview?fileId=${this.fileid}&x=1000&y=1000&a=1&${rnd}`;
        const padding = 15;

        this.$nextTick(() => {
            const el = document.getElementById(
                `drawoi-${rnd}`,
            );
            el.addEventListener('click', () => {
                this.openEditor();
            });
        });
        const img = new Image();
        img.onload = function() {
            const el = document.getElementById(
                `drawoi-${rnd}`,
            );
            const img = document.createElement('div');
            img.style.height = `${this.height}px`;
            img.style.width = `100%`;
            img.style.background = `url(${imgUrl}) no-repeat center/contain`;
            img.style.cursor = 'pointer',
            img.style.margin = `${padding}px`,

            el.appendChild(img);
            const h = this.height + padding * 2;
            el.style.height = `${h}px`;
            el.style.minHeight = `${h}px`;
        }
        img.src = imgUrl;
        return createElement(
            'div',
            {
                attrs: { id: `drawoi-${rnd}` },
                style: {
                    background: 'white',
                },
                class: [
                    'drawio',
                    'drawio-viewer__embedding',
                ],
            },
            '',
        );
    },
    computed: {
        isWB() {
            var extension = this.filename.substr(this.filename.lastIndexOf('.') + 1).toLowerCase();
           return String(extension == 'dwb');
        },
    },
    mounted() {
        if (!this.isEmbedded) {
            this.openEditor();
        }
    },
    methods: {
        openEditor() {
            OCA.DrawIO.OpenEditor(this.fileid, this.isWB);
        },
    },
};