{{-- Developer context: This Blade template renders the homebrew workshop page; PHP passes the category/status metadata in, and the browser script loads, filters, saves, and edits homebrew entries through the API. --}}
{{-- Clear explanation: This file is the homebrew workshop page where custom entries are created, filtered, and edited. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Homebrew | Adventurer's Ledger</title>
        <style>
            :root{--bg:#130f0d;--panel:#221915;--soft:#31231d;--line:#4e372a;--text:#f7ead8;--muted:#d8bea0;--accent:#d58345;--accent2:#efba70;--good:#476b3f}
            *{box-sizing:border-box} html{scroll-behavior:smooth;scroll-padding-top:7.5rem}
            body{position:relative;min-height:100vh;margin:0;font-family:"Trebuchet MS","Segoe UI",sans-serif;color:var(--text);background:#100c0b}
            body::before{content:"";position:fixed;inset:0;z-index:-2;background:radial-gradient(circle at top left,rgba(213,131,69,.22),transparent 25%),linear-gradient(160deg,#100c0b,#1b1411 50%,#0e0b0a)}
            body::after{content:"";position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(0,0,0,.06),rgba(0,0,0,.18))}
            button,input,textarea,select{font:inherit} a{text-decoration:none;color:inherit}
            .wrap{position:relative;z-index:1;width:min(1320px,calc(100% - 2rem));margin:0 auto;padding:1rem 0 4rem}
            .workspace{display:grid;grid-template-columns:210px minmax(0,1fr);gap:1rem;align-items:start}
            .topbar,.card,.panel,.notice,.mini,.entry{border:1px solid var(--line);border-radius:24px;background:rgba(34,25,21,.9);box-shadow:0 20px 50px rgba(0,0,0,.28)}
            .topbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem 1.2rem;margin-top:1rem;border-radius:999px;background:rgba(19,15,13,.84);backdrop-filter:blur(10px)}
            .brand{display:flex;align-items:center;gap:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
            .mark{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#25170f;font-weight:900}
            .nav{display:flex;flex-wrap:wrap;gap:.6rem}
            .nav a,.btn,.btn-soft{padding:.8rem 1rem;border-radius:999px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);cursor:pointer;transition:.18s ease}
            .btn:hover,.btn-soft:hover,.nav a:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
            .nav a.active{border-color:#8b623f;color:var(--text);background:rgba(255,255,255,.08)}
            .btn{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#29180f;font-weight:700}
            .page-rail{position:sticky;top:6.5rem;align-self:start;margin-top:2rem}
            .page-rail-card{padding:1rem}
            .page-rail-links{display:grid;gap:.6rem;margin-top:.8rem}
            .page-rail-links a{display:block;padding:.75rem .9rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);transition:.18s ease}
            .page-rail-links a:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
            main{display:grid;gap:1.4rem;grid-column:2}
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
            .actions-row{display:flex;flex-wrap:wrap;gap:.65rem;align-items:flex-start}
            .modal{position:fixed;inset:0;z-index:40;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(9,7,6,.72);backdrop-filter:blur(8px)}
            .modal.show{display:flex}
            .modal-panel{width:min(900px,100%);max-height:92vh;overflow:auto;padding:1.35rem}
            .modal-head{display:flex;justify-content:space-between;gap:1rem;align-items:start;margin-bottom:1rem}
            .modal-actions{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:flex-end;margin-top:1rem}
            @media (max-width:980px){.workspace,.hero,.grid,.entries{grid-template-columns:1fr}.toolbar-main{grid-template-columns:1fr}.topbar{position:static;border-radius:28px;align-items:stretch}.nav{justify-content:center}.page-rail{position:static;margin-top:0}.page-rail-links{grid-template-columns:repeat(2,minmax(0,1fr))}}
            @media (max-width:720px){.quick{grid-template-columns:1fr}.topbar{border-radius:26px;padding:1rem}.brand{justify-content:center}.nav a{flex:1 1 calc(50% - .6rem);text-align:center}}
            @media (max-width:640px){.wrap{width:min(100% - 1rem,100%)}.topbar,.card,.panel{padding:1.05rem}}
        </style>
    </head>
    <body>
        <div class="wrap">
            <header class="topbar">
                <a class="brand" href="{{ route('home') }}"><span class="mark">D20</span><span>Adventurer's Ledger</span></a>
                <nav class="nav">
                    <a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Builder</a>
                    <a class="{{ request()->routeIs('dm') ? 'active' : '' }}" href="{{ route('dm') }}">DM</a>
                    <a class="{{ request()->routeIs('roster') ? 'active' : '' }}" href="{{ route('roster') }}">Roster</a>
                    <a class="{{ request()->routeIs('homebrew') ? 'active' : '' }}" href="{{ route('homebrew') }}">Homebrew</a>
                    <a href="{{ url('/api') }}">API</a>
                </nav>
            </header>

            <div class="workspace">
                {{-- Developer context: This rail keeps the page-local navigation separate from the workshop content itself. --}}
                {{-- Clear explanation: This side block is the quick menu for moving around the homebrew page. --}}
                <aside class="page-rail">
                    <section class="panel page-rail-card">
                        <span class="eyebrow">On This Page</span>
                        <nav class="page-rail-links">
                            <a href="#overview">Overview</a>
                            <a href="#ground-rules">Ground Rules</a>
                            <a href="#create-homebrew">Create Homebrew</a>
                            <a href="#saved-homebrew">Saved Homebrew</a>
                        </nav>
                    </section>
                </aside>

            <main>
                {{-- Developer context: This overview section explains the purpose of the workshop and points toward its main actions. --}}
                {{-- Clear explanation: This block is the introduction to the homebrew page. --}}
                <section class="hero" id="overview">
                    <article class="card">
                        <span class="eyebrow">Homebrew Workshop</span>
                        <h1>Keep custom ideas separate from the verified sheet.</h1>
                        <p>{{ config('homebrew.official_note') }}</p>
                        <p class="tiny">Drafts, filters, and edit popups autosave locally in this browser, so a weak connection does not have to wipe your notes.</p>
                        <div class="notice" id="draft-notice"></div>
                        <div class="hero-actions">
                            <a class="btn" href="#create-homebrew">Create an entry</a>
                            <a class="btn-soft" href="{{ route('dm') }}">Open DM desk</a>
                            <a class="btn-soft" href="{{ route('home') }}#forge">Back to the builder</a>
                        </div>
                    </article>

                    {{-- Developer context: This side summary gives the workshop status at a glance without mixing it into the form flow. --}}
                    {{-- Clear explanation: This card shows a quick summary of the homebrew workshop. --}}
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

                {{-- Developer context: This section explains the boundary between official data and custom workshop entries. --}}
                {{-- Clear explanation: This block explains how homebrew stays separate from the official builder. --}}
                <section class="panel" id="ground-rules">
                    <span class="eyebrow">Ground Rules</span>
                    <h2>How this stays clean</h2>
                    <ul class="rule-list">
                        <li>The main builder, wizard, and library keep using the verified official catalog.</li>
                        <li>Nothing saved here is injected into the official dropdowns or rules responses.</li>
                        <li>Use this page to draft custom options, track playtest notes, and keep table-only material in one place.</li>
                    </ul>
                </section>

                {{-- Developer context: This section owns the workshop create form and its validation feedback. --}}
                {{-- Clear explanation: This block is where new homebrew entries are written and saved. --}}
                <section class="panel" id="create-homebrew">
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

                {{-- Developer context: This section owns the saved workshop list together with search, filters, and refresh controls. --}}
                {{-- Clear explanation: This block shows the saved homebrew entries and how to filter them. --}}
                <section class="panel" id="saved-homebrew">
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
        </div>

        {{-- Developer context: This modal keeps editing work isolated from the main workshop list until it is explicitly opened. --}}
        {{-- Clear explanation: This popup opens when someone edits an existing homebrew entry. --}}
        <div class="modal" id="homebrew-edit-modal" aria-hidden="true">
            <section class="panel modal-panel">
                <div class="modal-head">
                    <div>
                        <span class="eyebrow">Edit Homebrew</span>
                        <h2 id="homebrew-edit-title">Update the entry</h2>
                    </div>
                    <button class="btn-soft" id="homebrew-edit-close" type="button">Close</button>
                </div>
                <form class="form" id="homebrew-edit-form">
                    <input id="edit-homebrew-id" type="hidden">
                    <div class="grid">
                        <label>
                            Category
                            <select id="edit-homebrew-category" required></select>
                        </label>
                        <label>
                            Status
                            <select id="edit-homebrew-status" required></select>
                        </label>
                        <label class="full">
                            Name
                            <input id="edit-homebrew-name" type="text" maxlength="120" required>
                        </label>
                        <label class="full">
                            Summary
                            <textarea id="edit-homebrew-summary" maxlength="800" required></textarea>
                        </label>
                        <label class="full">
                            Details
                            <textarea id="edit-homebrew-details" maxlength="4000"></textarea>
                        </label>
                        <label class="full">
                            Source Notes
                            <textarea id="edit-homebrew-source-notes" maxlength="1200"></textarea>
                        </label>
                        <label class="full">
                            Tags
                            <input id="edit-homebrew-tags" type="text" maxlength="300" placeholder="arcane, support, travel">
                        </label>
                    </div>
                    <div class="modal-actions">
                        <button class="btn-soft" id="homebrew-edit-cancel" type="button">Cancel</button>
                        <button class="btn" type="submit">Save changes</button>
                    </div>
                </form>
            </section>
        </div>

        {{-- Developer context: This JSON payload passes the trusted PHP-side workshop metadata into the page so the browser script can build filters and labels without another request. --}}
        {{-- Clear explanation: This hidden data block gives the page the homebrew categories and statuses it needs to start. --}}
        <script id="homebrew-meta" type="application/json">{!! json_encode([
            'official_note' => config('homebrew.official_note'),
            'categories' => config('homebrew.categories', []),
            'statuses' => config('homebrew.statuses', []),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        <script>
            // Developer context: This boot block reads the server-provided workshop metadata, grabs the page elements the script will reuse, and prepares the local draft keys and in-memory entry list.
            // Clear explanation: These lines connect the homebrew page script to the saved entries and the visible controls on the page.
            const homebrewMeta = JSON.parse(document.getElementById('homebrew-meta').textContent);
            const categories = homebrewMeta.categories || {};
            const statuses = homebrewMeta.statuses || {};
            const countEl = document.getElementById('homebrew-count');
            const draftNotice = document.getElementById('draft-notice');
            const noticeEl = document.getElementById('homebrew-notice');
            const form = document.getElementById('homebrew-form');
            const entriesEl = document.getElementById('homebrew-entries');
            const searchInput = document.getElementById('homebrew-search');
            const categorySelect = document.getElementById('homebrew-category');
            const statusSelect = document.getElementById('homebrew-status');
            const categoryFilter = document.getElementById('homebrew-category-filter');
            const statusFilter = document.getElementById('homebrew-status-filter');
            const refreshButton = document.getElementById('homebrew-refresh');
            const homebrewEditModal = document.getElementById('homebrew-edit-modal');
            const homebrewEditForm = document.getElementById('homebrew-edit-form');
            const homebrewEditTitle = document.getElementById('homebrew-edit-title');
            // Developer context: This map keeps the browser storage keys in one place so create, filter, and edit drafts all reuse the same names consistently.
            // Clear explanation: These labels tell the browser where to save each homebrew draft.
            const localDraftKeys = {
                create: 'adventurers-ledger.homebrew-create-draft.v1',
                filters: 'adventurers-ledger.homebrew-filters.v1',
                editor: 'adventurers-ledger.homebrew-editor.v1',
            };
            // Developer context: These variables hold the current workshop entries and autosave timing between API calls, filters, and popup edits.
            // Clear explanation: These lines remember the current homebrew list while the page stays open.
            let homebrewEntries = [];
            let homebrewSaveTimer = null;

            // Developer context: Browserstorage updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function browserStorage() {
                try {
                    return window.localStorage;
                } catch {
                    return null;
                }
            }

            // Developer context: Readlocaldraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function readLocalDraft(key) {
                const storage = browserStorage();
                if (! storage) return null;

                try {
                    const raw = storage.getItem(key);
                    return raw ? JSON.parse(raw) : null;
                } catch {
                    return null;
                }
            }

            // Developer context: Writelocaldraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function writeLocalDraft(key, value) {
                const storage = browserStorage();
                if (! storage) return;

                try {
                    storage.setItem(key, JSON.stringify(value));
                } catch {
                    // Ignore storage write problems so the page stays usable.
                }
            }

            // Developer context: Removelocaldraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function removeLocalDraft(key) {
                const storage = browserStorage();
                if (! storage) return;

                try {
                    storage.removeItem(key);
                } catch {
                    // Ignore storage access problems so the page stays usable.
                }
            }

            // Developer context: This helper batches autosave writes so typing in the form or popup does not hit localStorage on every keystroke.
            // Clear explanation: This waits a moment before saving the page draft locally.
            function scheduleHomebrewDraftSave() {
                clearTimeout(homebrewSaveTimer);
                homebrewSaveTimer = window.setTimeout(persistHomebrewDrafts, 250);
            }

            // Developer context: This HTML-escaper is shared by entry rendering and error output so injected text stays text.
            // Clear explanation: This makes sure text from saved entries shows up safely instead of being treated like page code.
            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            // Developer context: This shared notice helper keeps success and error feedback consistent with the other pages.
            // Clear explanation: This shows a message box on the page.
            function notice(target, message, type) {
                if (! target) {
                    return;
                }

                target.textContent = message;
                target.className = `notice show ${type}`;
            }

            // Developer context: This companion helper resets a notice area without affecting the rest of the page state.
            // Clear explanation: This clears a visible page message.
            function clearNotice(target) {
                if (! target) {
                    return;
                }

                target.textContent = '';
                target.className = 'notice';
            }

            // Developer context: This restores the same "Draft restored" wording used across the other main pages.
            // Clear explanation: This tells the person which local drafts were brought back after a reload.
            function showDraftRestoreNotice(parts) {
                if (! draftNotice || ! Array.isArray(parts) || parts.length === 0) {
                    return;
                }

                notice(draftNotice, `Draft restored: ${parts.join(', ')}.`, 'success');
                window.setTimeout(() => {
                    clearNotice(draftNotice);
                }, 5000);
            }

            // Developer context: Populatemeta updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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

            // Developer context: Entrysearchtext updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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

            // Developer context: Renderentries updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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
                                <div class="actions-row">
                                    <button class="btn-soft" type="button" data-edit="${entry.id}">Edit</button>
                                    <button class="btn-soft" type="button" data-delete="${entry.id}">Delete</button>
                                </div>
                            </div>
                            <p>${escapeHtml(entry.summary)}</p>
                            ${entry.details ? `<div class="meta">${escapeHtml(entry.details).replaceAll('\n', '<br>')}</div>` : ''}
                            ${entry.source_notes ? `<ul class="entry-list"><li><strong>Table note:</strong> ${escapeHtml(entry.source_notes).replaceAll('\n', '<br>')}</li></ul>` : ''}
                            ${Array.isArray(entry.tags) && entry.tags.length ? `<div class="chip-list">${entry.tags.map((tag) => `<span class="chip">${escapeHtml(tag)}</span>`).join('')}</div>` : ''}
                        </article>
                    `;
                }).join('');
            }

            // Developer context: Commalist updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function commaList(values) {
                if (Array.isArray(values)) {
                    return values.join(', ');
                }

                return typeof values === 'string' ? values : '';
            }

            // Developer context: Createdraftfromform updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function createDraftFromForm() {
                return {
                    category: categorySelect.value,
                    status: statusSelect.value,
                    name: document.getElementById('homebrew-name').value,
                    summary: document.getElementById('homebrew-summary').value,
                    details: document.getElementById('homebrew-details').value,
                    source_notes: document.getElementById('homebrew-source-notes').value,
                    tags: document.getElementById('homebrew-tags').value,
                };
            }

            // Developer context: Fillhomebrewcreateform updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function fillHomebrewCreateForm(values) {
                categorySelect.value = values.category || categorySelect.value;
                statusSelect.value = values.status || 'draft';
                document.getElementById('homebrew-name').value = values.name || '';
                document.getElementById('homebrew-summary').value = values.summary || '';
                document.getElementById('homebrew-details').value = values.details || '';
                document.getElementById('homebrew-source-notes').value = values.source_notes || '';
                document.getElementById('homebrew-tags').value = values.tags || '';
            }

            // Developer context: Homebreweditdraftfromform updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function homebrewEditDraftFromForm() {
                return {
                    id: document.getElementById('edit-homebrew-id').value,
                    fields: {
                        category: document.getElementById('edit-homebrew-category').value,
                        status: document.getElementById('edit-homebrew-status').value,
                        name: document.getElementById('edit-homebrew-name').value,
                        summary: document.getElementById('edit-homebrew-summary').value,
                        details: document.getElementById('edit-homebrew-details').value,
                        source_notes: document.getElementById('edit-homebrew-source-notes').value,
                        tags: document.getElementById('edit-homebrew-tags').value,
                    },
                };
            }

            // Developer context: Fillhomebreweditform updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function fillHomebrewEditForm(values, entryId = '') {
                document.getElementById('edit-homebrew-id').value = entryId || values.id || '';
                document.getElementById('edit-homebrew-category').value = values.category || '';
                document.getElementById('edit-homebrew-status').value = values.status || 'draft';
                document.getElementById('edit-homebrew-name').value = values.name || '';
                document.getElementById('edit-homebrew-summary').value = values.summary || '';
                document.getElementById('edit-homebrew-details').value = values.details || '';
                document.getElementById('edit-homebrew-source-notes').value = values.source_notes || '';
                document.getElementById('edit-homebrew-tags').value = commaList(values.tags);
                homebrewEditTitle.textContent = `Edit ${values.name || 'entry'}`;
            }

            // Developer context: Persisthomebrewdrafts updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function persistHomebrewDrafts() {
                const createDraft = createDraftFromForm();
                if (Object.values(createDraft).some((value) => typeof value === 'string' ? value.trim() : value)) {
                    writeLocalDraft(localDraftKeys.create, createDraft);
                } else {
                    removeLocalDraft(localDraftKeys.create);
                }

                if (searchInput.value.trim() || categoryFilter.value || statusFilter.value) {
                    writeLocalDraft(localDraftKeys.filters, {
                        search: searchInput.value,
                        category: categoryFilter.value,
                        status: statusFilter.value,
                    });
                } else {
                    removeLocalDraft(localDraftKeys.filters);
                }

                if (homebrewEditModal.classList.contains('show') && document.getElementById('edit-homebrew-id').value) {
                    writeLocalDraft(localDraftKeys.editor, homebrewEditDraftFromForm());
                } else {
                    removeLocalDraft(localDraftKeys.editor);
                }
            }

            // Developer context: Restorehomebrewcreatedraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function restoreHomebrewCreateDraft() {
                const draft = readLocalDraft(localDraftKeys.create);
                if (draft && typeof draft === 'object') {
                    fillHomebrewCreateForm(draft);
                }
            }

            // Developer context: Restorehomebrewfilterdraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function restoreHomebrewFilterDraft() {
                const draft = readLocalDraft(localDraftKeys.filters);
                if (! draft || typeof draft !== 'object') {
                    return;
                }

                searchInput.value = typeof draft.search === 'string' ? draft.search : '';
                categoryFilter.value = typeof draft.category === 'string' ? draft.category : '';
                statusFilter.value = typeof draft.status === 'string' ? draft.status : '';
            }

            // Developer context: Restorehomebreweditordraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function restoreHomebrewEditorDraft() {
                if (homebrewEditModal.classList.contains('show')) {
                    return;
                }

                const draft = readLocalDraft(localDraftKeys.editor);
                if (! draft || ! draft.id || ! draft.fields || typeof draft.fields !== 'object') {
                    return;
                }

                const entry = homebrewEntries.find((item) => String(item.id) === String(draft.id));
                if (! entry) {
                    removeLocalDraft(localDraftKeys.editor);
                    return;
                }

                openHomebrewEditor(draft.id);
                fillHomebrewEditForm(draft.fields, draft.id);
                scheduleHomebrewDraftSave();
            }

            // Developer context: This opens the homebrew edit popup and fills it from the saved entry so edits start from the latest stored values.
            // Clear explanation: This opens the homebrew editor and loads that entry into the popup.
            function openHomebrewEditor(entryId) {
                const entry = homebrewEntries.find((item) => String(item.id) === String(entryId));

                if (! entry) {
                    notice(noticeEl, 'That homebrew entry could not be found for editing.', 'error');
                    return;
                }

                fillHomebrewEditForm(entry, entry.id);
                homebrewEditModal.classList.add('show');
                homebrewEditModal.setAttribute('aria-hidden', 'false');
                scheduleHomebrewDraftSave();
            }

            // Developer context: This resets the homebrew popup so an old draft does not spill into the next entry being edited.
            // Clear explanation: This closes the homebrew editor and clears the temporary edit draft.
            function closeHomebrewEditor() {
                homebrewEditForm.reset();
                document.getElementById('edit-homebrew-id').value = '';
                homebrewEditModal.classList.remove('show');
                homebrewEditModal.setAttribute('aria-hidden', 'true');
                removeLocalDraft(localDraftKeys.editor);
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
                    restoreHomebrewEditorDraft();
                } catch (error) {
                    entriesEl.innerHTML = `<div class="empty card"><span class="eyebrow">Load error</span><h3>Homebrew could not load.</h3><p>${escapeHtml(error.message)}</p></div>`;
                }
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearNotice(noticeEl);

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

                    notice(noticeEl, data.message || 'Homebrew entry saved.', 'success');
                    form.reset();
                    statusSelect.value = 'draft';
                    removeLocalDraft(localDraftKeys.create);
                    await loadEntries();
                } catch (error) {
                    notice(noticeEl, error.message || 'Homebrew could not be saved.', 'error');
                }
            });

            homebrewEditForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearNotice(noticeEl);

                const entryId = document.getElementById('edit-homebrew-id').value;
                if (! entryId) {
                    notice(noticeEl, 'No homebrew entry is selected for editing right now.', 'error');
                    return;
                }

                const payload = {
                    category: document.getElementById('edit-homebrew-category').value,
                    status: document.getElementById('edit-homebrew-status').value,
                    name: document.getElementById('edit-homebrew-name').value,
                    summary: document.getElementById('edit-homebrew-summary').value,
                    details: document.getElementById('edit-homebrew-details').value,
                    source_notes: document.getElementById('edit-homebrew-source-notes').value,
                    tags: document.getElementById('edit-homebrew-tags').value,
                };

                try {
                    const response = await fetch(`/api/homebrew/${entryId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (! response.ok) {
                        const firstError = Object.values(data.errors || {}).flat()[0] || data.message || 'Homebrew could not be updated.';
                        throw new Error(firstError);
                    }

                    closeHomebrewEditor();
                    notice(noticeEl, data.message || 'Homebrew entry updated.', 'success');
                    await loadEntries();
                } catch (error) {
                    notice(noticeEl, error.message || 'Homebrew could not be updated.', 'error');
                }
            });

            entriesEl.addEventListener('click', async (event) => {
                const editButton = event.target.closest('[data-edit]');
                if (editButton) {
                    openHomebrewEditor(editButton.dataset.edit);
                    return;
                }

                const button = event.target.closest('[data-delete]');
                if (! button) {
                    return;
                }

                const entryId = button.dataset.delete;
                if (! entryId || ! window.confirm('Delete this homebrew entry?')) {
                    return;
                }

                clearNotice(noticeEl);

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

                    notice(noticeEl, data.message || 'Homebrew entry removed.', 'success');
                    await loadEntries();
                } catch (error) {
                    notice(noticeEl, error.message || 'Homebrew could not be removed.', 'error');
                }
            });

            [searchInput, categoryFilter, statusFilter].forEach((control) => {
                control.addEventListener('input', () => {
                    renderEntries();
                    scheduleHomebrewDraftSave();
                });
                control.addEventListener('change', () => {
                    renderEntries();
                    scheduleHomebrewDraftSave();
                });
            });

            document.getElementById('homebrew-edit-close').addEventListener('click', closeHomebrewEditor);
            document.getElementById('homebrew-edit-cancel').addEventListener('click', closeHomebrewEditor);
            homebrewEditModal.addEventListener('click', (event) => {
                if (event.target === homebrewEditModal) {
                    closeHomebrewEditor();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && homebrewEditModal.classList.contains('show')) {
                    closeHomebrewEditor();
                }
            });
            refreshButton.addEventListener('click', loadEntries);
            form.addEventListener('input', scheduleHomebrewDraftSave);
            form.addEventListener('change', scheduleHomebrewDraftSave);
            homebrewEditForm.addEventListener('input', scheduleHomebrewDraftSave);
            homebrewEditForm.addEventListener('change', scheduleHomebrewDraftSave);

            populateMeta();
            statusSelect.value = 'draft';
            document.getElementById('edit-homebrew-category').innerHTML = categorySelect.innerHTML;
            document.getElementById('edit-homebrew-status').innerHTML = statusSelect.innerHTML;
            document.getElementById('edit-homebrew-status').value = 'draft';
            const restoredSections = [];
            if (readLocalDraft(localDraftKeys.create)) {
                restoreHomebrewCreateDraft();
                restoredSections.push('create form');
            }
            if (readLocalDraft(localDraftKeys.filters)) {
                restoreHomebrewFilterDraft();
                restoredSections.push('filters');
            }
            window.addEventListener('beforeunload', persistHomebrewDrafts);
            window.setInterval(persistHomebrewDrafts, 10000);
            loadEntries();
            if (readLocalDraft(localDraftKeys.editor)) {
                restoredSections.push('edit popup');
            }
            showDraftRestoreNotice(restoredSections);
        </script>
    </body>
</html>
