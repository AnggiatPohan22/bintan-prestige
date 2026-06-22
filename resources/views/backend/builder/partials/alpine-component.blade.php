<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pageBuilder', (cfg) => ({

        /* ── State ─────────────────────────────────────────── */
        tree:         [],
        isDirty:      false,
        isSaving:     false,
        saveError:    null,
        isRefreshing: false,
        previewError: null,
        activeTab:    'insert',   // 'insert' | 'tree'
        selectedCid:  null,       // _cid of the block selected in tree list
        previewMode:    'desktop', // 'desktop' | 'tablet' | 'mobile'
        leftCollapsed:  false,     // minimize / close the left (blocks) panel
        rightCollapsed: false,     // minimize / close the right (settings) panel
        _cid:           0,
        _refreshTimer:  null,

        /* ── Init ──────────────────────────────────────────── */
        init() {
            this.tree = this.tagCids(JSON.parse(JSON.stringify(cfg.tree)));
            // On narrow screens the side panels open as overlay drawers — start them
            // closed so the canvas is usable immediately; desktop keeps both open.
            if (window.innerWidth < 1024) {
                this.leftCollapsed  = true;
                this.rightCollapsed = true;
            }
            this.$nextTick(() => this.refreshPreview());
        },

        tagCids(nodes) {
            return nodes.map(n => ({
                ...n,
                _cid:     ++this._cid,
                children: this.tagCids(n.children || []),
            }));
        },

        /* ── Preview ───────────────────────────────────────── */
        scheduleRefresh() {
            this.isDirty = true;
            clearTimeout(this._refreshTimer);
            this._refreshTimer = setTimeout(() => this.refreshPreview(), 800);
        },

        async refreshPreview() {
            this.isRefreshing = true;
            this.previewError = null;
            try {
                const res = await fetch(cfg.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'Accept': '*/*',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ blocks: this.serialize(this.tree) }),
                });
                if (!res.ok || res.redirected) {
                    const ct = res.headers.get('content-type') || '';
                    if (ct.includes('application/json')) {
                        try {
                            const json = await res.json();
                            this.previewError = `Preview failed: ${json.message || 'Validation error'}`;
                        } catch {
                            this.previewError = `Preview failed: HTTP ${res.status} ${res.statusText}`;
                        }
                    } else {
                        this.previewError = `Preview failed: HTTP ${res.status} ${res.statusText}`;
                    }
                } else {
                    const html  = await res.text();
                    const frame = document.getElementById('builder-preview');
                    if (frame) {
                        // The iframe scrolls its OWN content (it holds the full page at
                        // device width and fills the visible canvas height), so preserve
                        // the iframe's scroll position across refreshes.
                        let prevScroll = 0;
                        try { prevScroll = frame.contentWindow?.scrollY || 0; } catch {}
                        frame.addEventListener('load', () => {
                            requestAnimationFrame(() => {
                                try { frame.contentWindow.scrollTo(0, prevScroll); } catch {}
                            });
                        }, { once: true });
                        frame.srcdoc = html;
                    }
                }
            } catch (e) {
                this.previewError = `Preview error: ${e.message || 'Network error'}`;
            }
            this.isRefreshing = false;
        },

        serialize(nodes) {
            return nodes.map((n, i) => ({
                block_type: n.type,
                label:      n.label,
                data:       n.data || {},
                sort_order: i,
                is_visible: n.is_visible !== false,
                children:   this.serialize(n.children || []),
            }));
        },

        /* ── Save ──────────────────────────────────────────── */
        async saveTree() {
            this.isSaving  = true;
            this.saveError = null;
            try {
                const res = await fetch(cfg.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ blocks: this.serialize(this.tree) }),
                });
                const json = await res.json();
                if (json.success) {
                    this.tree    = this.tagCids(json.tree);
                    this.isDirty = false;
                } else {
                    this.saveError = json.message || 'Save failed.';
                }
            } catch {
                this.saveError = 'Network error — please try again.';
            }
            this.isSaving = false;
        },

        /* ── Block operations ──────────────────────────────── */
        addBlock(type, label) {
            const node = {
                _cid:       ++this._cid,
                id:         null,
                type,
                label,
                data:       {},
                is_visible: true,
                sort_order: this.tree.length,
                children:   [],
            };
            if (this.selectedCid !== null) {
                const idx = this.tree.findIndex(n => n._cid === this.selectedCid);
                if (idx !== -1) {
                    this.tree.splice(idx + 1, 0, node);
                } else {
                    this.tree.push(node);
                }
            } else {
                this.tree.push(node);
            }
            this.applyFieldDefaults(node);
            this.selectedCid = node._cid;
            this.scheduleRefresh();
        },

        selectBlock(cid) {
            this.selectedCid = this.selectedCid === cid ? null : cid;
            const node = this.selectedNode();
            if (node) this.applyFieldDefaults(node);
        },

        /* ── Settings panel (B3) ───────────────────────────── */
        // The root-level node currently selected (null = nothing selected).
        selectedNode() {
            if (this.selectedCid === null) return null;
            return this.tree.find(n => n._cid === this.selectedCid) || null;
        },

        // Editable field schema for a block type, from config/blocks.php (cfg.registry).
        fieldsFor(type) {
            const def = cfg.registry?.[type];
            return (def && def.fields) ? def.fields : [];
        },

        // Ensure every schema field exists in node.data so inputs/selects bind to a
        // defined value (selects show their default; preview stays consistent).
        applyFieldDefaults(node) {
            if (!node) return;
            node.data = node.data || {};
            for (const f of this.fieldsFor(node.type)) {
                if (node.data[f.key] === undefined) {
                    node.data[f.key] = (f.default !== undefined)
                        ? f.default
                        : (f.type === 'toggle' ? false : '');
                }
            }
        },

        onSort(cidStr, newPos) {
            const cid    = +cidStr;
            const oldPos = this.tree.findIndex(n => n._cid === cid);
            if (oldPos === -1 || oldPos === newPos) return;
            const [item] = this.tree.splice(oldPos, 1);
            this.tree.splice(newPos, 0, item);
            this.scheduleRefresh();
        },

        toggleVisible(node) {
            node.is_visible = !node.is_visible;
            this.scheduleRefresh();
        },

        moveUp(index) {
            if (index === 0) return;
            [this.tree[index - 1], this.tree[index]] = [this.tree[index], this.tree[index - 1]];
            this.tree = [...this.tree];
            this.scheduleRefresh();
        },

        moveDown(index) {
            if (index >= this.tree.length - 1) return;
            [this.tree[index], this.tree[index + 1]] = [this.tree[index + 1], this.tree[index]];
            this.tree = [...this.tree];
            this.scheduleRefresh();
        },

        removeBlock(index) {
            this.tree.splice(index, 1);
            this.tree = [...this.tree];
            if (!this.tree.some(n => n._cid === this.selectedCid)) this.selectedCid = null;
            this.scheduleRefresh();
        },

        /* ── Category label helper ─────────────────────────── */
        catLabel(cat) {
            return {
                layout:     'Layout',
                content:    'Content',
                media:      'Media',
                conversion: 'Conversion',
                travel:     'Travel',
            }[cat] || cat;
        },

        catIcon(cat) {
            return {
                layout:     'fa-table-columns',
                content:    'fa-file-lines',
                media:      'fa-image',
                conversion: 'fa-arrow-pointer',
                travel:     'fa-plane',
            }[cat] || 'fa-cube';
        },

        /* ── Preview mode (Elementor-style device width) ───────
           The page renders at a real device WIDTH inside the iframe:
           desktop = fluid (100% of the canvas), tablet = 768px, mobile = 375px.
           The device width is applied as a plain CSS max-width on the iframe
           wrapper — NO transform/scale and NO JS measurement — so the rendered
           site is identical at any panel width, and the iframe fills the visible
           canvas height and scrolls its own content (hero → footer).
           ──────────────────────────────────────────────────── */
        deviceMaxWidth() {
            return { desktop: '100%', tablet: '768px', mobile: '375px' }[this.previewMode];
        },

        blockIcon(type) {
            const icons = {
                group: 'fa-layer-group', columns: 'fa-table-columns',
                hero: 'fa-image', heading: 'fa-heading', text: 'fa-align-left',
                image: 'fa-image', gallery: 'fa-images', video_embed: 'fa-video',
                button_group: 'fa-hand-pointer', stats: 'fa-chart-bar',
                tour_itinerary: 'fa-route', pricing_table: 'fa-tags',
                cta: 'fa-bullhorn', products_grid: 'fa-grid-2',
                faq: 'fa-circle-question', testimonials: 'fa-comment',
                map: 'fa-location-dot', divider: 'fa-minus',
                contact_form: 'fa-envelope',
            };
            return icons[type] || 'fa-cube';
        },
    }));
});
</script>
