<style>
.tag-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #e8edf5; border-radius: 20px;
    padding: 2px 10px 2px 12px; font-size: .82rem; color: #333;
}
.tag-chip button {
    background: none; border: none; padding: 0; line-height: 1;
    color: #888; cursor: pointer; font-size: .9rem;
}
.tag-chip button:hover { color: #dc3545; }
</style>

<!-- 태그 -->
<div class="mb-3">
    <label class="form-label small fw-semibold"><i class="bi bi-tags me-1"></i><?= lang('App.tags_label') ?></label>
    <div class="d-flex flex-wrap gap-1 mb-2" id="tagChips"></div>
    <div class="input-group" style="max-width:380px">
        <input type="text" id="tagInput" class="form-control form-control-sm"
               placeholder="<?= lang('App.tag_placeholder') ?>" maxlength="64">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addTag()"><?= lang('App.add') ?></button>
    </div>
    <div id="tagHiddens"></div>
    <div class="form-text"><?= lang('App.tags_hint') ?></div>
</div>

<!-- URL -->
<div class="mb-4">
    <label class="form-label small fw-semibold"><i class="bi bi-link-45deg me-1"></i><?= lang('App.related_links') ?></label>
    <div id="urlList"></div>
    <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="addUrl('')">
        <i class="bi bi-plus me-1"></i><?= lang('App.add_url') ?>
    </button>
</div>

<script>
(function () {
    /* ── 태그 ── */
    const chips    = document.getElementById('tagChips');
    const hiddens  = document.getElementById('tagHiddens');
    const tagInput = document.getElementById('tagInput');
    const initTags = <?= json_encode(array_column($existingTags, 'tag')) ?>;

    function renderTags() {
        const vals = [...hiddens.querySelectorAll('input')].map(i => i.value);
        chips.innerHTML = '';
        vals.forEach((t, i) => {
            const chip = document.createElement('span');
            chip.className = 'tag-chip';
            chip.innerHTML = `${escHtml(t)}<button type="button" onclick="removeTag(${i})" title="<?= lang('App.delete') ?>">×</button>`;
            chips.appendChild(chip);
        });
    }

    window.addTag = function (val) {
        const raw = (val ?? tagInput.value).split(',');
        raw.forEach(t => {
            t = t.trim();
            if (! t) return;
            const existing = [...hiddens.querySelectorAll('input')].map(i => i.value);
            if (existing.includes(t)) return;
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'tags[]'; inp.value = t;
            hiddens.appendChild(inp);
        });
        tagInput.value = '';
        renderTags();
    };

    window.removeTag = function (idx) {
        hiddens.querySelectorAll('input')[idx]?.remove();
        renderTags();
    };

    tagInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); addTag(); }
    });

    initTags.forEach(t => addTag(t));

    /* ── URL ── */
    const urlList  = document.getElementById('urlList');
    const initUrls = <?= json_encode(array_column($existingUrls, 'url')) ?>;

    window.addUrl = function (val) {
        const row = document.createElement('div');
        row.className = 'input-group mb-2';
        row.style.maxWidth = '540px';
        row.innerHTML = `
            <input type="text" name="urls[]" class="form-control form-control-sm"
                   placeholder="https://..." value="${escHtml(val ?? '')}">
            <button type="button" class="btn btn-sm btn-outline-danger"
                    onclick="this.closest('.input-group').remove()">
                <i class="bi bi-x-lg"></i>
            </button>`;
        urlList.appendChild(row);
    };

    initUrls.forEach(u => addUrl(u));

    /* ── util ── */
    function escHtml(s) {
        return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
