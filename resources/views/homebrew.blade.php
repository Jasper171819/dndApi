<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Homebrew | Adventurer's Ledger</title>
        <style>
            :root{--bg:#130f0d;--panel:#221915;--soft:#31231d;--line:#4e372a;--text:#f7ead8;--muted:#d8bea0;--accent:#d58345;--accent2:#efba70;--good:#476b3f}
            *{box-sizing:border-box} html{scroll-behavior:smooth}
            body{position:relative;min-height:100vh;margin:0;font-family:"Trebuchet MS","Segoe UI",sans-serif;color:var(--text);background:#100c0b}
            body::before{content:"";position:fixed;inset:0;z-index:-2;background:radial-gradient(circle at top left,rgba(213,131,69,.22),transparent 25%),linear-gradient(160deg,#100c0b,#1b1411 50%,#0e0b0a)}
            body::after{content:"";position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(0,0,0,.06),rgba(0,0,0,.18))}
            button,input,textarea,select{font:inherit} a{text-decoration:none;color:inherit}
            .wrap{position:relative;z-index:1;width:min(1180px,calc(100% - 2rem));margin:0 auto;padding:1rem 0 4rem}
            .topbar,.card,.panel,.notice,.mini,.entry{border:1px solid var(--line);border-radius:24px;background:rgba(34,25,21,.9);box-shadow:0 20px 50px rgba(0,0,0,.28)}
            .topbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem 1.2rem;margin-top:1rem;border-radius:999px;background:rgba(19,15,13,.84);backdrop-filter:blur(10px)}
            .brand{display:flex;align-items:center;gap:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
            .mark{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#25170f;font-weight:900}
            .nav{display:flex;flex-wrap:wrap;gap:.6rem}
            .nav a,.btn,.btn-soft{padding:.8rem 1rem;border-radius:999px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);cursor:pointer;transition:.18s ease}
            .btn:hover,.btn-soft:hover,.nav a:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
            .btn{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#29180f;font-weight:700}
            main{display:grid;gap:1.4rem}
            .hero{display:grid;grid-template-columns:1.15fr .85fr;gap:1rem;padding:2rem 0 0}
            .card,.panel{padding:1.5rem}
            .eyebrow{display:inline-block;margin-bottom:.8rem;color:var(--accent2);font-size:.78rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
            h1,h2,h3{margin:0;font-family:Georgia,"Times New Roman",serif;line-height:1.05}
            h1{font-size:clamp(2.5rem,5vw,4.2rem);max-width:11ch}
            h2{font-size:clamp(1.7rem,3vw,2.2rem)}
            p{color:var(--muted);line-height:1.7}
            .hero-actions,.toolbar,.actions,.quick{display:flex;flex-wrap:wrap;gap:.75rem}
            .quick{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));margin-top:1rem}
            .mini{padding:1rem;border-radius:18px;background:var(--soft)}
            .mini strong{display:block;margin-top:.35rem;font-size:1.15rem;color:var(--text)}
            .rule-list,.entry-list{margin:.8rem 0 0;padding-left:1rem;color:var(--muted)}
            .rule-list li,.entry-list li{margin:.35rem 0}
            .tiny{font-size:.92rem;color:rgba(247,234,216,.68)}
            .form{display:grid;gap:1rem}
            .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
            label{display:grid;gap:.35rem;color:var(--muted);font-size:.92rem}
            input,textarea,select{width:100%;padding:.85rem 1rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--text);outline:none}
            textarea{min-height:7rem;resize:vertical}
            input:focus,textarea:focus,select:focus{border-color:#9e754f}
            .full{grid-column:1 / -1}
            .notice{display:none;padding:1rem 1.1rem;border-radius:18px}
            .notice.show{display:block}
            .notice.error{color:#ffd9d9;border-color:#7b4a4a;background:rgba(123,74,74,.18)}
            .notice.success{color:#d7f0dc;border-color:#4d7556;background:rgba(77,117,86,.18)}
            .toolbar{align-items:end;justify-content:space-between}
            .toolbar-main{display:grid;grid-template-columns:minmax(240px,1fr) 190px 190px auto;gap:.75rem;flex:1}
            .entries{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
            .entry{padding:1rem;display:grid;gap:.85rem;background:rgba(255,255,255,.03)}
            .entry-top{display:flex;justify-content:space-between;gap:1rem;align-items:start}
            .meta{color:var(--muted);font-size:.94rem}
            .chip-list{display:flex;flex-wrap:wrap;gap:.45rem}
            .chip{padding:.35rem .6rem;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid var(--line);font-size:.82rem;color:var(--muted)}
            .chip.good{border-color:rgba(71,107,63,.9);background:rgba(43,76,49,.5);color:#ecf8ea}
            .empty{padding:2.25rem;text-align:center}
            @media (max-width:980px){.hero,.grid,.entries{grid-template-columns:1fr}.toolbar-main{grid-template-columns:1fr}.topbar{position:static;border-radius:28px;align-items:stretch}.nav{justify-content:center}}
            @media (max-width:720px){.quick{grid-template-columns:1fr}.topbar{border-radius:26px;padding:1rem}.brand{justify-content:center}.nav a{flex:1 1 calc(50% - .6rem);text-align:center}}
            @media (max-width:640px){.wrap{width:min(100% - 1rem,100%)}.topbar,.card,.panel{padding:1.05rem}}
        </style>
    </head>
    <body>
        <div class="wrap">
            <header class="topbar">
                <a class="brand" href="{{ route('home') }}"><span class="mark">D20</span><span>Adventurer's Ledger</span></a>
                <nav class="nav">
                    <a href="{{ route('home') }}#forge">Builder</a>
                    <a href="{{ route('home') }}#wizard">Wizard</a>
                    <a href="{{ route('home') }}#library">Library</a>
                    <a href="{{ route('roster') }}">Roster</a>
                    <a href="{{ route('homebrew') }}">Homebrew</a>
                </nav>
            </header>

            <main>
                <section class="hero">
                    <article class="card">
                        <span class="eyebrow">Homebrew Workshop</span>
                        <h1>Keep custom ideas separate from the verified sheet.</h1>
                        <p>{{ config('homebrew.official_note') }}</p>
                        <div class="hero-actions">
                            <a class="btn" href="#homebrew-form">Create an entry</a>
                            <a class="btn-soft" href="{{ route('home') }}#forge">Back to the builder</a>
                        </div>
                    </article>

                    <aside class="card">
                        <span class="eyebrow">At a Glance</span>
                        <div class="quick">
                            <div class="mini">Official Builder<strong>Still rules-first</strong></div>
                            <div class="mini">Homebrew Entries<strong id="homebrew-count">0</strong></div>
                            <div class="mini">Categories<strong>{{ count(config('homebrew.categories', [])) }}</strong></div>
                            <div class="mini">Workflow<strong>Draft, playtest, table ready</strong></div>
                        </div>
                    </aside>
                </section>

                <section class="panel">
                    <span class="eyebrow">Ground Rules</span>
                    <h2>How this stays clean</h2>
                    <ul class="rule-list">
                        <li>The main builder, wizard, and library keep using the verified official catalog.</li>
                        <li>Nothing saved here is injected into the official dropdowns or rules responses.</li>
                        <li>Use this page to draft custom options, track playtest notes, and keep table-only material in one place.</li>
                    </ul>
                </section>

                <section class="panel">
                    <div>
                        <span class="eyebrow">Create Homebrew</span>
                        <h2>Draft a custom entry</h2>
                        <p class="tiny">Write a clean summary first, then add longer details or table notes only if you need them.</p>
                    </div>
                    <div class="notice" id="homebrew-notice"></div>
                    <form class="form" id="homebrew-form">
                        <div class="grid">
                            <label>
                                Category
                                <select id="homebrew-category" name="category" required></select>
                            </label>
                            <label>
                                Status
                                <select id="homebrew-status" name="status" required></select>
                            </label>
                            <label class="full">
                                Name
                                <input id="homebrew-name" name="name" type="text" maxlength="120" placeholder="Storm Scholar, Ashen Grove, Graveglass Lantern..." required>
                            </label>
                            <label class="full">
                                Summary
                                <textarea id="homebrew-summary" name="summary" maxlength="800" placeholder="A clear one-paragraph overview of what this homebrew is and how it feels at the table." required></textarea>
                            </label>
                            <label class="full">
                                Details
                                <textarea id="homebrew-details" name="details" maxlength="4000" placeholder="Mechanical notes, lore, feature outline, challenge idea, or anything else the table needs."></textarea>
                            </label>
                            <label class="full">
                                Source Notes
                                <textarea id="homebrew-source-notes" name="source_notes" maxlength="1200" placeholder="Optional note about inspiration, campaign use, or how this differs from the official rules."></textarea>
                            </label>
                            <label class="full">
                                Tags
                                <input id="homebrew-tags" name="tags" type="text" maxlength="300" placeholder="arcane, urban, stealth, boss fight, support">
                            </label>
                        </div>
                        <div class="actions">
                            <button class="btn" type="submit">Save homebrew</button>
                            <button class="btn-soft" id="homebrew-reset" type="reset">Clear form</button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <div class="toolbar">
                        <div>
                            <span class="eyebrow">Saved Homebrew</span>
                            <h2>Browse the workshop</h2>
                        </div>
                        <div class="toolbar-main">
                            <input id="homebrew-search" type="text" placeholder="Search by name, category, summary, notes, or tags">
                            <select id="homebrew-category-filter">
                                <option value="">All categories</option>
                            </select>
                            <select id="homebrew-status-filter">
                                <option value="">All statuses</option>
                            </select>
                            <button class="btn-soft" id="homebrew-refresh" type="button">Refresh</button>
                        </div>
                    </div>
                    <p class="tiny">These entries are workshop notes. They stay separate from the official builder data unless you later choose to wire them in on purpose.</p>
                    <div class="entries" id="homebrew-entries"></div>
                </section>
            </main>
        </div>

        <script id="homebrew-meta" type="application/json">{!! json_encode([
            'official_note' => config('homebrew.official_note'),
            'categories' => config('homebrew.categories', []),
            'statuses' => config('homebrew.statuses', []),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        <script>
            const homebrewMeta = JSON.parse(document.getElementById('homebrew-meta').textContent);
            const categories = homebrewMeta.categories || {};
            const statuses = homebrewMeta.statuses || {};
            const countEl = document.getElementById('homebrew-count');
            const noticeEl = document.getElementById('homebrew-notice');
            const form = document.getElementById('homebrew-form');
            const entriesEl = document.getElementById('homebrew-entries');
            const searchInput = document.getElementById('homebrew-search');
            const categorySelect = document.getElementById('homebrew-category');
            const statusSelect = document.getElementById('homebrew-status');
            const categoryFilter = document.getElementById('homebrew-category-filter');
            const statusFilter = document.getElementById('homebrew-status-filter');
            const refreshButton = document.getElementById('homebrew-refresh');
            let homebrewEntries = [];

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function showNotice(message, type) {
                noticeEl.textContent = message;
                noticeEl.className = `notice show ${type}`;
            }

            function clearNotice() {
                noticeEl.textContent = '';
                noticeEl.className = 'notice';
            }

            function populateMeta() {
                const categoryOptions = Object.entries(categories)
                    .map(([value, category]) => `<option value="${escapeHtml(value)}">${escapeHtml(category.label)}</option>`)
                    .join('');
                const statusOptions = Object.entries(statuses)
                    .map(([value, status]) => `<option value="${escapeHtml(value)}">${escapeHtml(status.label)}</option>`)
                    .join('');

                categorySelect.innerHTML = categoryOptions;
                statusSelect.innerHTML = statusOptions;
                categoryFilter.innerHTML += categoryOptions;
                statusFilter.innerHTML += statusOptions;
            }

            function entrySearchText(entry) {
                return [
                    entry.name,
                    entry.category,
                    entry.status,
                    entry.summary,
                    entry.details,
                    entry.source_notes,
                    ...(Array.isArray(entry.tags) ? entry.tags : []),
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();
            }

            function renderEntries() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                const categoryTerm = categoryFilter.value;
                const statusTerm = statusFilter.value;

                const filtered = homebrewEntries.filter((entry) => {
                    if (categoryTerm && entry.category !== categoryTerm) {
                        return false;
                    }

                    if (statusTerm && entry.status !== statusTerm) {
                        return false;
                    }

                    if (! searchTerm) {
                        return true;
                    }

                    return entrySearchText(entry).includes(searchTerm);
                });

                countEl.textContent = homebrewEntries.length;

                if (! filtered.length) {
                    entriesEl.innerHTML = homebrewEntries.length
                        ? `<div class="empty card"><span class="eyebrow">No matches</span><h3>Nothing fits that filter yet.</h3><p>Try a broader search or clear the filters to see every homebrew entry again.</p></div>`
                        : `<div class="empty card"><span class="eyebrow">No homebrew yet</span><h3>The workshop is empty.</h3><p>Create a custom entry here and it will stay separate from the official builder.</p></div>`;
                    return;
                }

                entriesEl.innerHTML = filtered.map((entry) => {
                    const category = categories[entry.category] || { label: entry.category, hint: '' };
                    const status = statuses[entry.status] || { label: entry.status, hint: '' };
                    const created = entry.created_at ? new Date(entry.created_at).toLocaleDateString() : '';

                    return `
                        <article class="entry">
                            <div class="entry-top">
                                <div>
                                    <div class="chip-list">
                                        <span class="chip">${escapeHtml(category.label)}</span>
                                        <span class="chip good">${escapeHtml(status.label)}</span>
                                    </div>
                                    <h3 style="margin-top:.65rem">${escapeHtml(entry.name)}</h3>
                                    <div class="meta">${escapeHtml(category.hint || '')}</div>
                                    ${created ? `<div class="meta">Saved ${escapeHtml(created)}</div>` : ''}
                                </div>
                                <button class="btn-soft" type="button" data-delete="${entry.id}">Delete</button>
                            </div>
                            <p>${escapeHtml(entry.summary)}</p>
                            ${entry.details ? `<div class="meta">${escapeHtml(entry.details).replaceAll('\n', '<br>')}</div>` : ''}
                            ${entry.source_notes ? `<ul class="entry-list"><li><strong>Table note:</strong> ${escapeHtml(entry.source_notes).replaceAll('\n', '<br>')}</li></ul>` : ''}
                            ${Array.isArray(entry.tags) && entry.tags.length ? `<div class="chip-list">${entry.tags.map((tag) => `<span class="chip">${escapeHtml(tag)}</span>`).join('')}</div>` : ''}
                        </article>
                    `;
                }).join('');
            }

            async function loadEntries() {
                try {
                    const response = await fetch('/api/homebrew');
                    if (! response.ok) {
                        throw new Error('Could not load homebrew entries right now.');
                    }

                    const payload = await response.json();
                    homebrewEntries = Array.isArray(payload.entries) ? payload.entries : [];
                    renderEntries();
                } catch (error) {
                    entriesEl.innerHTML = `<div class="empty card"><span class="eyebrow">Load error</span><h3>Homebrew could not load.</h3><p>${escapeHtml(error.message)}</p></div>`;
                }
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearNotice();

                const payload = {
                    category: categorySelect.value,
                    status: statusSelect.value,
                    name: document.getElementById('homebrew-name').value,
                    summary: document.getElementById('homebrew-summary').value,
                    details: document.getElementById('homebrew-details').value,
                    source_notes: document.getElementById('homebrew-source-notes').value,
                    tags: document.getElementById('homebrew-tags').value,
                };

                try {
                    const response = await fetch('/api/homebrew', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (! response.ok) {
                        const firstError = Object.values(data.errors || {}).flat()[0] || data.message || 'Homebrew could not be saved.';
                        throw new Error(firstError);
                    }

                    showNotice(data.message || 'Homebrew entry saved.', 'success');
                    form.reset();
                    statusSelect.value = 'draft';
                    await loadEntries();
                } catch (error) {
                    showNotice(error.message || 'Homebrew could not be saved.', 'error');
                }
            });

            entriesEl.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-delete]');
                if (! button) {
                    return;
                }

                const entryId = button.dataset.delete;
                if (! entryId || ! window.confirm('Delete this homebrew entry?')) {
                    return;
                }

                clearNotice();

                try {
                    const response = await fetch(`/api/homebrew/${entryId}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (! response.ok) {
                        throw new Error(data.message || 'Homebrew could not be removed.');
                    }

                    showNotice(data.message || 'Homebrew entry removed.', 'success');
                    await loadEntries();
                } catch (error) {
                    showNotice(error.message || 'Homebrew could not be removed.', 'error');
                }
            });

            [searchInput, categoryFilter, statusFilter].forEach((control) => {
                control.addEventListener('input', renderEntries);
                control.addEventListener('change', renderEntries);
            });

            refreshButton.addEventListener('click', loadEntries);

            populateMeta();
            statusSelect.value = 'draft';
            loadEntries();
        </script>
    </body>
</html>
