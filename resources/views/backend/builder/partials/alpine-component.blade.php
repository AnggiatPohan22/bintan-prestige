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
        activeTab:    'insert',   // 'insert' | 'patterns' | 'tree'
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
        inlineToolbar:  { visible: false, left: 0, top: 0 },
        _inlineRange:    null,
        _inlineTarget:   null,
        patterns:        [],
        patternsLoading: false,
        patternsError:   null,
        patternFormOpen: false,
        isSavingPattern: false,
        patternDraft:    { name: '', category: '', description: '' },
        templateModalOpen: false,
        layoutTemplates: cfg.layoutTemplates || [],
        currentTemplateId: cfg.currentTemplateId ?? null,
        builderTemplates: [],
        templatesLoading: false,
        templateError: null,
        templateMeta: { current_page: 1, last_page: 1, total: 0 },
        templateSearch: '',
        templateFormOpen: false,
        isSavingTemplate: false,
        templateDraft: { name: '', category: '', description: '' },
        _templatesLoaded: false,

        /* ── Init ──────────────────────────────────────────── */
        init() {
            this.tree = this.tagCids(JSON.parse(JSON.stringify(cfg.tree)));
            // On narrow screens the side panels open as overlay drawers — start them
            // closed so the canvas is usable immediately; desktop keeps both open.
            if (window.innerWidth < 1024) {
                this.leftCollapsed  = true;
                this.rightCollapsed = true;
            }
            this.loadPatterns();
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
                    body: JSON.stringify({
                        blocks: this.serialize(this.tree),
                        template_id: this.currentTemplateId,
                    }),
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
                                this.bindInlineEditing(frame);
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
                    body: JSON.stringify({
                        blocks: this.serialize(this.tree),
                        template_id: this.currentTemplateId,
                    }),
                });
                const json = await res.json();
                if (json.success) {
                    this.tree    = this.tagCids(json.tree);
                    this.currentTemplateId = json.template_id ?? null;
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

            this.placeNode(node);
        },

        placeNode(node) {

            const sel = this.selectedNode();
            if (sel && this.isContainer(sel)) {
                // A container is selected → drop the new block INSIDE it.
                if (sel.type === 'columns' && node.type !== 'group') {
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

        /* â”€â”€ Reusable patterns (B5) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
        async loadPatterns() {
            this.patternsLoading = true;
            this.patternsError = null;
            try {
                const res = await fetch(cfg.patternsUrl, {
                    headers: { 'Accept': 'application/json' },
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Unable to load patterns.');
                this.patterns = json.patterns || [];
            } catch (error) {
                this.patternsError = error.message || 'Unable to load patterns.';
            } finally {
                this.patternsLoading = false;
            }
        },

        openPatternForm() {
            const node = this.selectedNode();
            if (!node) return;
            this.patternDraft = {
                name: node.label || cfg.registry?.[node.type]?.label || node.type,
                category: '',
                description: '',
            };
            this.patternFormOpen = true;
        },

        async saveSelectedPattern() {
            const node = this.selectedNode();
            if (!node || !this.patternDraft.name.trim()) return;

            this.isSavingPattern = true;
            this.patternsError = null;
            try {
                const res = await fetch(cfg.patternsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.patternDraft,
                        pattern_data: this.serialize([node])[0],
                    }),
                });
                const json = await res.json();
                if (!res.ok) {
                    const firstError = Object.values(json.errors || {})[0];
                    throw new Error(firstError?.[0] || json.message || 'Unable to save pattern.');
                }

                this.patterns.unshift(json.pattern);
                this.patternFormOpen = false;
                this.patternDraft = { name: '', category: '', description: '' };
                this.activeTab = 'patterns';
                this.flash('Pattern saved.');
            } catch (error) {
                this.patternsError = error.message || 'Unable to save pattern.';
            } finally {
                this.isSavingPattern = false;
            }
        },

        hydratePattern(node) {
            return {
                _cid: ++this._cid,
                id: null,
                type: node.block_type,
                label: node.label,
                data: JSON.parse(JSON.stringify(node.data || {})),
                is_visible: node.is_visible !== false,
                sort_order: 0,
                children: (node.children || []).map(child => this.hydratePattern(child)),
            };
        },

        insertPattern(pattern) {
            if (!pattern?.pattern_data) return;
            const node = this.hydratePattern(JSON.parse(JSON.stringify(pattern.pattern_data)));
            this.placeNode(node);
            this.flash(`Inserted pattern: ${pattern.name}`);
        },

        async deletePattern(pattern) {
            this.patternsError = null;
            try {
                const res = await fetch(`${cfg.patternsUrl}/${pattern.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': cfg.csrf,
                        'Accept': 'application/json',
                    },
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Unable to delete pattern.');
                this.patterns = this.patterns.filter(item => item.id !== pattern.id);
                this.flash('Pattern deleted. Inserted page blocks were not changed.');
            } catch (error) {
                this.patternsError = error.message || 'Unable to delete pattern.';
            }
        },

        /* -- Full-page template library (B6) -------------------------------- */
        openTemplateLibrary() {
            this.templateModalOpen = true;
            if (!this._templatesLoaded) this.loadBuilderTemplates(1);
        },

        async loadBuilderTemplates(page = 1) {
            this.templatesLoading = true;
            this.templateError = null;
            try {
                const url = new URL(cfg.builderTemplatesUrl, window.location.origin);
                url.searchParams.set('page', page);
                if (this.templateSearch.trim()) url.searchParams.set('search', this.templateSearch.trim());

                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Unable to load templates.');

                this.builderTemplates = json.templates || [];
                this.templateMeta = json.meta || { current_page: 1, last_page: 1, total: 0 };
                this._templatesLoaded = true;
            } catch (error) {
                this.templateError = error.message || 'Unable to load templates.';
            } finally {
                this.templatesLoading = false;
            }
        },

        currentLayoutName() {
            return this.layoutTemplates.find(layout => Number(layout.id) === Number(this.currentTemplateId))?.name || 'Standard';
        },

        applyLayoutTemplate(layout) {
            this.currentTemplateId = layout.id;
            this.scheduleRefresh();
            this.flash(`Layout selected: ${layout.name}. Save the page to persist it.`);
        },

        openTemplateSaveForm() {
            this.templateDraft = {
                name: cfg.pageTitle || 'Page Template',
                category: '',
                description: '',
            };
            this.templateFormOpen = true;
        },

        async saveBuilderTemplate() {
            if (!this.templateDraft.name.trim()) return;

            this.isSavingTemplate = true;
            this.templateError = null;
            try {
                const res = await fetch(cfg.storeBuilderTemplateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': cfg.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.templateDraft,
                        base_template_id: this.currentTemplateId,
                        template_data: this.serialize(this.tree),
                    }),
                });
                const json = await res.json();
                if (!res.ok) {
                    const firstError = Object.values(json.errors || {})[0];
                    throw new Error(firstError?.[0] || json.message || 'Unable to save template.');
                }

                this.builderTemplates.unshift(json.template);
                this.templateMeta.total = Number(this.templateMeta.total || 0) + 1;
                this._templatesLoaded = true;
                this.templateFormOpen = false;
                this.templateDraft = { name: '', category: '', description: '' };
                this.flash('Page template saved.');
            } catch (error) {
                this.templateError = error.message || 'Unable to save template.';
            } finally {
                this.isSavingTemplate = false;
            }
        },

        hydrateTemplateTree(nodes) {
            return (nodes || []).map(node => this.hydratePattern(node));
        },

        async applyBuilderTemplate(template) {
            if (this.tree.length && !window.confirm('Replace the current canvas with this template? Unsaved blocks will be replaced.')) return;

            this.templatesLoading = true;
            this.templateError = null;
            try {
                const res = await fetch(`${cfg.builderTemplatesUrl}/${template.id}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Unable to load this template.');

                this.tree = this.hydrateTemplateTree(json.template.template_data);
                this.selectedCid = null;
                this.currentTemplateId = json.template.base_template_id ?? null;
                this.templateModalOpen = false;
                this.scheduleRefresh();
                this.flash(`Applied template: ${json.template.name}. Save the page to persist it.`);
            } catch (error) {
                this.templateError = error.message || 'Unable to apply template.';
            } finally {
                this.templatesLoading = false;
            }
        },

        async deleteBuilderTemplate(template) {
            this.templateError = null;
            try {
                const res = await fetch(`${cfg.builderTemplatesUrl}/${template.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': cfg.csrf,
                        'Accept': 'application/json',
                    },
                });
                const json = await res.json();
                if (!res.ok) throw new Error(json.message || 'Unable to delete template.');

                this.builderTemplates = this.builderTemplates.filter(item => item.id !== template.id);
                this.templateMeta.total = Math.max(0, Number(this.templateMeta.total || 0) - 1);
                this.flash('Template deleted. Existing pages were not changed.');
            } catch (error) {
                this.templateError = error.message || 'Unable to delete template.';
            }
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

        inlineField(type, key) {
            return this.fieldsFor(type).find(field => field.key === key && field.inline) || null;
        },

        flattenTree(nodes = this.tree, result = []) {
            for (const node of nodes) {
                result.push(node);
                this.flattenTree(node.children || [], result);
            }
            return result;
        },

        bindInlineEditing(frame) {
            const doc = frame.contentDocument;
            if (!doc) return;

            const style = doc.createElement('style');
            style.textContent = `
                [contenteditable]:hover {
                    outline:1px dashed var(--builder-edit-hint,#94a3b8);
                    outline-offset:2px;
                    cursor:text;
                }
                [contenteditable]:focus {
                    outline:2px solid var(--builder-edit-active,#3b82f6);
                    outline-offset:2px;
                    min-height:1em;
                }
                [contenteditable]:empty::before {
                    content:attr(data-placeholder);
                    color:var(--builder-placeholder,#9ca3af);
                    pointer-events:none;
                    font-style:italic;
                }
            `;
            doc.head.appendChild(style);

            const nodes = this.flattenTree();
            doc.querySelectorAll('[data-builder-block-order]').forEach(root => {
                const order = Number(root.dataset.builderBlockOrder);
                const node = nodes[order - 1];
                if (!node) return;

                root.querySelectorAll('[contenteditable][data-inline-field]').forEach(editable => {
                    const field = editable.dataset.inlineField;
                    const definition = this.inlineField(node.type, field);
                    if (!definition || definition.inline !== editable.dataset.editType) return;

                    editable.addEventListener('focus', () => {
                        this.selectedCid = node._cid;
                        this.activeFieldTab = 'layout';
                    });
                    editable.addEventListener('click', event => {
                        if (editable.closest('a')) event.preventDefault();
                    });
                    editable.addEventListener('input', () => {
                        node.data[field] = definition.inline === 'richtext'
                            ? editable.innerHTML
                            : editable.innerText.replace(/\r/g, '');
                        this.isDirty = true;
                        clearTimeout(this._refreshTimer);
                        this._refreshTimer = setTimeout(() => this.refreshPreview(), 800);
                    });
                    editable.addEventListener('blur', () => {
                        if (definition.inline !== 'richtext') this.hideInlineToolbar();
                    });
                });
            });

            doc.addEventListener('selectionchange', () => this.updateInlineToolbar(frame));
            doc.addEventListener('mousedown', event => {
                if (!event.target.closest?.('[contenteditable][data-edit-type="richtext"]')) {
                    this.hideInlineToolbar();
                }
            });
        },

        updateInlineToolbar(frame) {
            const doc = frame.contentDocument;
            const selection = doc?.getSelection();
            const anchor = selection?.anchorNode;
            const anchorElement = anchor?.nodeType === 1 ? anchor : anchor?.parentElement;
            const editable = anchorElement?.closest?.('[contenteditable][data-edit-type="richtext"]');

            if (!selection || selection.rangeCount === 0 || selection.isCollapsed || !editable) {
                this.hideInlineToolbar();
                return;
            }

            const range = selection.getRangeAt(0);
            if (!editable.contains(range.commonAncestorContainer)) {
                this.hideInlineToolbar();
                return;
            }

            const rect = range.getBoundingClientRect();
            const frameRect = frame.getBoundingClientRect();
            this._inlineRange = range.cloneRange();
            this._inlineTarget = editable;
            this.inlineToolbar = {
                visible: true,
                left: Math.max(8, frameRect.left + rect.left + (rect.width / 2)),
                top: Math.max(8, frameRect.top + rect.top - 44),
            };
        },

        formatInline(command) {
            if (!this._inlineRange || !this._inlineTarget) return;
            const doc = this._inlineTarget.ownerDocument;
            const selection = doc.getSelection();
            selection.removeAllRanges();
            selection.addRange(this._inlineRange);

            if (command === 'link') {
                const url = window.prompt('Link URL (http:// or https://)');
                if (!url || !/^https?:\/\//i.test(url)) return;
                doc.execCommand('createLink', false, url);
                const link = selection.anchorNode?.parentElement?.closest?.('a');
                if (link) link.setAttribute('target', '_blank');
            } else if (command === 'clear') {
                doc.execCommand('removeFormat', false, null);
                doc.execCommand('unlink', false, null);
            } else {
                doc.execCommand(command, false, null);
            }

            this._inlineTarget.dispatchEvent(new Event('input', { bubbles: true }));
            this._inlineRange = selection.rangeCount ? selection.getRangeAt(0).cloneRange() : null;
        },

        hideInlineToolbar() {
            this.inlineToolbar.visible = false;
            this._inlineRange = null;
            this._inlineTarget = null;
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
            form.append('collection', 'content');
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

        /* ── B7 responsive helpers ─────────────────────────── */

        // Human-readable label for the current preview mode (shown in canvas status bar).
        previewModeLabel() {
            return {
                desktop: 'Desktop · Fluid width',
                tablet:  'Tablet · 768 px',
                mobile:  'Mobile · 375 px',
            }[this.previewMode] || '';
        },

        // True when the block is set to hide on the currently active device.
        // Reads hide_desktop / hide_tablet / hide_mobile from the Advanced tab.
        isHiddenOnDevice(node) {
            const d = node?.data || {};
            if (this.previewMode === 'desktop') return !!d.hide_desktop;
            if (this.previewMode === 'tablet')  return !!d.hide_tablet;
            if (this.previewMode === 'mobile')  return !!d.hide_mobile;
            return false;
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
