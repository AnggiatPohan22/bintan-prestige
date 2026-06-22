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
        activeFieldTab: 'layout', // settings panel tab: 'layout' | 'style' | 'advanced'
        selectedCid:  null,       // _cid of the block selected in tree list
        previewMode:    'desktop', // 'desktop' | 'tablet' | 'mobile'
        leftCollapsed:  false,     // minimize / close the left (blocks) panel
        rightCollapsed: false,     // minimize / close the right (settings) panel
        _cid:           0,
        _refreshTimer:  null,
        _pickSetter:    null,      // pending Media Library target setter
        nestHint:       null,      // transient hint shown when a nesting rule applies
        _hintTimer:     null,
        dragCid:        null,      // _cid of the block being dragged
        dragOverCid:    null,      // _cid of the row currently hovered as a drop target
        dropMode:       null,      // 'before' | 'after' | 'inside' | 'invalid'

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

        /* ── Block operations (nesting-aware) ──────────────── */
        addBlock(type, label) {
            const node = {
                _cid:       ++this._cid,
                id:         null,
                type,
                label,
                data:       {},
                is_visible: true,
                sort_order: 0,
                children:   [],
            };
            this.applyFieldDefaults(node);

            const sel = this.selectedNode();
            if (sel && this.isContainer(sel)) {
                // A container is selected → drop the new block INSIDE it.
                if (sel.type === 'columns' && type !== 'group') {
                    // Columns may only hold Group blocks → place it right after instead.
                    const ctx = this.findCtx(sel._cid);
                    ctx.arr.splice(ctx.index + 1, 0, node);
                    this.flash('Columns can contain Group blocks only — added after it instead.');
                } else {
                    sel.children = sel.children || [];
                    sel.children.push(node);
                }
            } else if (sel) {
                // A normal block is selected → insert as its next sibling.
                const ctx = this.findCtx(sel._cid);
                ctx.arr.splice(ctx.index + 1, 0, node);
            } else {
                this.tree.push(node);
            }
            this.selectedCid = node._cid;
            this.scheduleRefresh();
        },

        selectBlock(cid) {
            this.selectedCid = this.selectedCid === cid ? null : cid;
            this.activeFieldTab = 'layout';
            const node = this.selectedNode();
            if (node) this.applyFieldDefaults(node);
        },

        // Settings-panel tabs (only shown when a block uses more than one tab).
        fieldTab(field) { return field.tab || 'layout'; },

        fieldTabs() {
            const present = new Set(this.fieldsFor(this.selectedNode()?.type).map(f => this.fieldTab(f)));
            return ['layout', 'style', 'advanced'].filter(t => present.has(t));
        },

        /* ── Tree helpers (nesting) ────────────────────────── */
        // The node currently selected, searched recursively (null = none).
        selectedNode() {
            if (this.selectedCid === null) return null;
            const ctx = this.findCtx(this.selectedCid);
            return ctx ? ctx.node : null;
        },

        // Locate a node + its containing array / index / parent anywhere in the tree.
        findCtx(cid, nodes = this.tree, parent = null) {
            for (let i = 0; i < nodes.length; i++) {
                if (nodes[i]._cid === cid) return { node: nodes[i], arr: nodes, index: i, parent };
                const hit = this.findCtx(cid, nodes[i].children || [], nodes[i]);
                if (hit) return hit;
            }
            return null;
        },

        isContainer(node) { return !!node && (node.type === 'group' || node.type === 'columns'); },

        childCount(node) { return (node && node.children) ? node.children.length : 0; },

        // Depth-first flattened view for the Block List (each row carries its depth).
        flatList() {
            const out = [];
            const walk = (nodes, depth) => {
                for (const n of nodes) {
                    out.push({ node: n, depth });
                    if (n.children && n.children.length) walk(n.children, depth + 1);
                }
            };
            walk(this.tree, 0);
            return out;
        },

        // True when `cid` is inside `node`'s subtree (used to block invalid drops).
        contains(node, cid) {
            for (const c of (node?.children || [])) {
                if (c._cid === cid || this.contains(c, cid)) return true;
            }
            return false;
        },

        /* ── Drag & drop (reorder + nest) ──────────────────── */
        onDragStart(cid, e) {
            this.dragCid = cid;
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                try { e.dataTransfer.setData('text/plain', String(cid)); } catch (_) {}
            }
        },

        // Decide the drop intent from the cursor position within the hovered row:
        // a container's middle band = drop INSIDE, its edges (and any leaf) = reorder.
        onDragOver(cid, e) {
            if (this.dragCid === null) return;
            const drag = this.findCtx(this.dragCid)?.node;
            const target = this.findCtx(cid)?.node;
            if (!drag || !target) return;

            const r = e.currentTarget.getBoundingClientRect();
            const ratio = (e.clientY - r.top) / (r.height || 1);

            let mode;
            if (this.isContainer(target)) {
                mode = ratio < 0.25 ? 'before' : ratio > 0.78 ? 'after' : 'inside';
            } else {
                mode = ratio < 0.5 ? 'before' : 'after';
            }

            // Validity: never drop onto itself or into its own subtree; Columns hold Groups only.
            if (cid === this.dragCid || this.contains(drag, cid)) {
                mode = 'invalid';
            } else if (mode === 'inside' && target.type === 'columns' && drag.type !== 'group') {
                mode = 'invalid';
            }

            this.dragOverCid = cid;
            this.dropMode = mode;
        },

        onDrop(cid) {
            const mode = this.dropMode;
            const dragCid = this.dragCid;
            this.clearDrag();
            if (dragCid === null || cid === dragCid || mode === 'invalid' || !mode) {
                if (mode === 'invalid') this.flash('Can’t drop there — Columns hold Group blocks only, and a block can’t go inside itself.');
                return;
            }
            this.moveNode(dragCid, cid, mode);
        },

        clearDrag() { this.dragCid = null; this.dragOverCid = null; this.dropMode = null; },

        // Re-parent / reorder a node relative to a target.
        moveNode(dragCid, targetCid, mode) {
            const dctx = this.findCtx(dragCid);
            if (!dctx) return;
            const node = dctx.node;
            if (dragCid === targetCid || this.contains(node, targetCid)) return;

            // Remove from its current spot first, then locate the target afresh
            // (indices may shift) and insert.
            dctx.arr.splice(dctx.index, 1);
            const tctx = this.findCtx(targetCid);
            if (!tctx) { this.tree.push(node); }
            else if (mode === 'inside') {
                tctx.node.children = tctx.node.children || [];
                tctx.node.children.push(node);
            } else if (mode === 'before') {
                tctx.arr.splice(tctx.index, 0, node);
            } else {
                tctx.arr.splice(tctx.index + 1, 0, node);
            }

            this.selectedCid = node._cid;
            this.tree = [...this.tree];
            this.scheduleRefresh();
        },

        flash(msg) {
            this.nestHint = msg;
            clearTimeout(this._hintTimer);
            this._hintTimer = setTimeout(() => { this.nestHint = null; }, 3500);
        },

        // Editable field schema for a block type, from config/blocks.php (cfg.registry).
        fieldsFor(type) {
            const def = cfg.registry?.[type];
            return (def && def.fields) ? def.fields : [];
        },

        // Default value for one field by type (fresh objects each call).
        defaultFor(f) {
            if (f.type === 'box') return { top: '', right: '', bottom: '', left: '' };
            if (f.type === 'background') return { color: '', image: '', position: 'center', size: 'cover', repeat: 'no-repeat', opacity: 100 };
            if (f.default !== undefined) return f.default;
            if (f.type === 'repeater' || f.type === 'list') return [];
            if (f.type === 'toggle') return false;
            return '';
        },

        // Ensure every schema field exists in node.data so inputs/selects bind to a
        // defined value (selects show their default; preview stays consistent).
        applyFieldDefaults(node) {
            if (!node) return;
            node.data = node.data || {};
            for (const f of this.fieldsFor(node.type)) {
                if (node.data[f.key] === undefined) {
                    node.data[f.key] = this.defaultFor(f);
                }
            }
        },

        /* ── Repeater (array of objects) ───────────────────── */
        // Build a fresh repeater row from its sub-field schema defaults.
        newRepeaterItem(field) {
            const item = {};
            for (const sub of (field.fields || [])) item[sub.key] = this.defaultFor(sub);
            return item;
        },

        repeaterArr(field) {
            const node = this.selectedNode();
            if (!node) return [];
            if (!Array.isArray(node.data[field.key])) node.data[field.key] = [];
            return node.data[field.key];
        },

        repeaterAdd(field) {
            if (field.max && this.repeaterArr(field).length >= field.max) return;
            this.repeaterArr(field).push(this.newRepeaterItem(field));
            this.scheduleRefresh();
        },

        repeaterRemove(field, index) {
            this.repeaterArr(field).splice(index, 1);
            this.scheduleRefresh();
        },

        /* ── List (array of plain strings) ─────────────────── */
        listAdd(field) {
            if (field.max && this.repeaterArr(field).length >= field.max) return;
            this.repeaterArr(field).push('');
            this.scheduleRefresh();
        },

        listRemove(field, index) {
            this.repeaterArr(field).splice(index, 1);
            this.scheduleRefresh();
        },

        /* ── Select options (static object or dynamic source) ── */
        selectOptions(field) {
            if (field.optionsFrom) return (cfg.options?.[field.optionsFrom]) || [];
            return Object.entries(field.options || {}).map(([value, label]) => ({ value, label }));
        },

        /* ── Conditional fields (showIf: {key, value}) ─────── */
        showField(field) {
            if (!field.showIf) return true;
            const node = this.selectedNode();
            return node ? node.data?.[field.showIf.key] === field.showIf.value : true;
        },

        /* ── Image fields: preview, media picker, direct upload ─ */
        mediaPreview(path) {
            if (!path) return '';
            return path.startsWith('http') ? path : '/storage/' + path;
        },

        // Open the shared Media Library modal; remember where to write the result.
        pickImage(setter) {
            this._pickSetter = setter;
            this.$dispatch('open-media-picker', { target: 'builder' });
        },

        onMediaPicked(detail) {
            if (detail.target !== 'builder' || typeof this._pickSetter !== 'function') return;
            this._pickSetter(detail.media?.path || '');
            this._pickSetter = null;
            this.scheduleRefresh();
        },

        async uploadInto(event, setter) {
            const file = event.target.files[0];
            if (!file) return;
            const form = new FormData();
            form.append('image', file);
            form.append('_token', cfg.csrf);
            try {
                const res = await fetch(cfg.uploadUrl, { method: 'POST', body: form });
                const data = await res.json();
                if (res.ok && data.success) { setter(data.path); this.scheduleRefresh(); }
                else { this.saveError = data.message || 'Upload failed.'; }
            } catch {
                this.saveError = 'Upload failed. Please try again.';
            } finally {
                event.target.value = '';
            }
        },

        toggleVisible(node) {
            node.is_visible = !node.is_visible;
            this.scheduleRefresh();
        },

        // Remove a block (and, for a container, its children) anywhere in the tree.
        removeBlock(cid) {
            const ctx = this.findCtx(cid);
            if (!ctx) return;
            ctx.arr.splice(ctx.index, 1);
            if (this.selectedCid === cid || !this.findCtx(this.selectedCid)) this.selectedCid = null;
            this.tree = [...this.tree];
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
