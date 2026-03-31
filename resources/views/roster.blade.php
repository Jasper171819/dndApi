{{-- Developer context: This Blade template renders the roster page; it receives metadata from PHP and uses the browser script to load characters from the API, filter them, and edit them in the popup. --}}
{{-- Clear explanation: This file is the saved-character roster page, including the search view and the edit popup. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Roster | Adventurer's Ledger</title>
        <style>
            :root{--bg:#130f0d;--panel:#221915;--soft:#31231d;--line:#4e372a;--text:#f7ead8;--muted:#d8bea0;--accent:#d58345;--accent2:#efba70}
            *{box-sizing:border-box} html{scroll-behavior:smooth;scroll-padding-top:7.5rem}
            body{position:relative;min-height:100vh;margin:0;font-family:"Trebuchet MS","Segoe UI",sans-serif;color:var(--text);background:#100c0b}
            body::before{content:"";position:fixed;inset:0;z-index:-2;background:radial-gradient(circle at top left,rgba(213,131,69,.22),transparent 25%),linear-gradient(160deg,#100c0b,#1b1411 50%,#0e0b0a)}
            body::after{content:"";position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(0,0,0,.06),rgba(0,0,0,.18))}
            button,input,textarea,select{font:inherit} a{text-decoration:none;color:inherit}
            .wrap{position:relative;z-index:1;width:min(1320px,calc(100% - 2rem));margin:0 auto;padding:1rem 0 4rem}
            .workspace{display:grid;grid-template-columns:210px minmax(0,1fr);gap:1rem;align-items:start}
            .topbar,.card,.panel,.char,.stat,.notice,.mini{border:1px solid var(--line);border-radius:24px;background:rgba(34,25,21,.9);box-shadow:0 20px 50px rgba(0,0,0,.28)}
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
            .hero{display:grid;grid-template-columns:1.1fr .9fr;gap:1rem;padding:2rem 0 0}
            .card,.panel{padding:1.5rem}
            .eyebrow{display:inline-block;margin-bottom:.8rem;color:var(--accent2);font-size:.78rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
            h1,h2,h3{margin:0;font-family:Georgia,"Times New Roman",serif;line-height:1.05}
            h1{font-size:clamp(2.5rem,5vw,4.1rem);max-width:10ch}
            h2{font-size:clamp(1.7rem,3vw,2.2rem)}
            p{color:var(--muted);line-height:1.7}
            .hero-actions,.toolbar{display:flex;flex-wrap:wrap;gap:.75rem}
            .quick{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));margin-top:1rem;gap:.75rem}
            .mini{padding:1rem;border-radius:18px;background:var(--soft)}
            .mini strong{display:block;margin-top:.35rem;font-size:1.15rem;color:var(--text)}
            .toolbar{justify-content:space-between;align-items:end}
            .toolbar-main{display:flex;flex-wrap:wrap;gap:.75rem;flex:1}
            .toolbar input{flex:1;min-width:240px;padding:.9rem 1rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--text);outline:none}
            .toolbar input:focus{border-color:#9e754f}
            .notice{display:none;padding:1rem 1.1rem;margin-bottom:1rem;border-radius:18px}
            .notice.show{display:block}.notice.error{color:#ffd9d9;border-color:#7b4a4a;background:rgba(123,74,74,.18)}.notice.success{color:#d7f0dc;border-color:#4d7556;background:rgba(77,117,86,.18)}
            .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
            .char{padding:1rem;display:grid;gap:.9rem;background:rgba(255,255,255,.03)}
            .char-top{display:flex;justify-content:space-between;gap:1rem;align-items:start}
            .meta{color:var(--muted);font-size:.95rem}
            .stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem}
            .stat{padding:.75rem;border-radius:16px;text-align:center;background:rgba(255,255,255,.03)}
            .stat span{display:block}.stat .label{font-size:.72rem;letter-spacing:.08em;color:var(--accent2)} .stat .value{margin-top:.2rem;font-weight:700}
            .tiny{font-size:.92rem;color:rgba(247,234,216,.65)}
            .empty{padding:2.25rem;text-align:center}
            .stack{display:grid;gap:1rem}
            .actions-row{display:flex;flex-wrap:wrap;gap:.65rem;align-items:flex-start}
            .modal{position:fixed;inset:0;z-index:40;display:none;align-items:center;justify-content:center;padding:1rem;background:rgba(9,7,6,.72);backdrop-filter:blur(8px)}
            .modal.show{display:flex}
            .modal-panel{width:min(980px,100%);max-height:92vh;overflow:auto;padding:1.35rem}
            .modal-head{display:flex;justify-content:space-between;gap:1rem;align-items:start;margin-bottom:1rem}
            .modal-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.9rem}
            .modal-grid .full{grid-column:1 / -1}
            label{display:grid;gap:.35rem;color:var(--muted);font-size:.92rem}
            textarea,select,input[type="number"],input[type="text"]{width:100%;padding:.85rem 1rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--text);outline:none}
            textarea{min-height:6rem;resize:vertical}
            textarea:focus,select:focus,input[type="number"]:focus,input[type="text"]:focus{border-color:#9e754f}
            .modal-actions{display:flex;flex-wrap:wrap;gap:.75rem;justify-content:flex-end;margin-top:1rem}
            @media (max-width:980px){.workspace,.hero,.grid,.modal-grid{grid-template-columns:1fr}.page-rail{position:static;margin-top:0}.page-rail-links{grid-template-columns:repeat(2,minmax(0,1fr))}}
            @media (max-width:720px){.toolbar{flex-direction:column;align-items:stretch}.toolbar-main{width:100%}.quick{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.topbar{border-radius:26px}}
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
                {{-- Developer context: This rail keeps page-local jumps separate from the roster content and controls. --}}
                {{-- Clear explanation: This side block is the quick menu for moving around the roster page. --}}
                <aside class="page-rail">
                    <section class="panel page-rail-card">
                        <span class="eyebrow">On This Page</span>
                        <nav class="page-rail-links">
                            <a href="#overview">Overview</a>
                            <a href="#saved-characters">Saved Characters</a>
                        </nav>
                    </section>
                </aside>

                <main>
                {{-- Developer context: This overview section introduces the roster page and its most common next actions. --}}
                {{-- Clear explanation: This block is the roster page introduction. --}}
                <section class="hero" id="overview">
                    <article class="card">
                        <span class="eyebrow">Roster</span>
                        <h1>Keep the whole party in view.</h1>
                        <p>See every saved character in one place, search the group quickly, and tidy the roster without losing track of who is who. The number shown on each card is just that character's current roster slot on this page.</p>
                        <p class="tiny">Search text and edit popups autosave locally in this browser, so a reload does not have to wipe what you were changing.</p>
                        <div class="notice" id="draft-notice"></div>
                        <div class="hero-actions">
                            <a class="btn" href="{{ route('home') }}#forge">Create a character</a>
                            <a class="btn-soft" href="{{ route('dm') }}">Open DM desk</a>
                            <a class="btn-soft" href="{{ route('home') }}#wizard">Open the wizard</a>
                        </div>
                    </article>

                    {{-- Developer context: This side summary surfaces the current roster state without forcing a full scan of the grid. --}}
                    {{-- Clear explanation: This card shows the quick roster summary. --}}
                    <aside class="card">
                        <span class="eyebrow">At a Glance</span>
                        <div class="quick">
                            <div class="mini">Characters<strong id="count">0</strong></div>
                            <div class="mini">View<strong id="search-state">All</strong></div>
                            <div class="mini">Roster Slots<strong>Close gaps</strong></div>
                            <div class="mini">Controls<strong>Refresh or remove</strong></div>
                        </div>
                    </aside>
                </section>

                {{-- Developer context: This section owns roster search, refresh, and the rendered list of saved characters. --}}
                {{-- Clear explanation: This block is where the saved characters are searched and shown. --}}
                <section class="panel" id="saved-characters">
                    <div class="toolbar">
                        <div>
                            <span class="eyebrow">Saved Characters</span>
                            <h2>Browse the party</h2>
                        </div>
                        <div class="toolbar-main">
                            <input id="roster-search" type="text" placeholder="Search by name, species, class, background, goal, notes, or language">
                            <button class="btn-soft" id="refresh" type="button">Refresh</button>
                        </div>
                    </div>
                    <p class="tiny">Roster numbers are recalculated from the current list each time the page refreshes, so deleting a character closes the gap instead of leaving old slot numbers behind.</p>
                    <div class="notice" id="roster-notice"></div>
                    <div class="grid" id="characters"></div>
                </section>
            </main>
            </div>
        </div>

        {{-- Developer context: This modal keeps sheet editing separate from the read-only roster list until the user opens it. --}}
        {{-- Clear explanation: This popup opens when someone edits a saved character. --}}
        <div class="modal" id="character-edit-modal" aria-hidden="true">
            <section class="panel modal-panel">
                <div class="modal-head">
                    <div>
                        <span class="eyebrow">Edit Character</span>
                        <h2 id="character-edit-title">Update the sheet</h2>
                    </div>
                    <button class="btn-soft" id="character-edit-close" type="button">Close</button>
                </div>
                <form class="stack" id="character-edit-form">
                    <input id="edit-character-id" type="hidden">
                    <div class="modal-grid">
                        <label>
                            Name
                            <input id="edit-name" type="text" maxlength="255" required>
                        </label>
                        <label>
                            Level
                            <input id="edit-level" type="number" min="1" max="20" required>
                        </label>
                        <label>
                            Advancement Method
                            <select id="edit-advancement-method" required></select>
                        </label>
                        <label>
                            Species
                            <select id="edit-species" required></select>
                        </label>
                        <label>
                            Class
                            <select id="edit-class" required></select>
                        </label>
                        <label>
                            Subclass
                            <select id="edit-subclass" required></select>
                        </label>
                        <label>
                            Background
                            <select id="edit-background" required></select>
                        </label>
                        <label>
                            Alignment
                            <select id="edit-alignment"></select>
                        </label>
                        <label>
                            Origin Feat
                            <select id="edit-origin-feat" required></select>
                        </label>
                        <label class="full">
                            Languages
                            <input id="edit-languages" type="text" maxlength="400" placeholder="Common, Elvish">
                        </label>
                        <label class="full">
                            Skill Proficiencies
                            <input id="edit-skill-proficiencies" type="text" maxlength="500" placeholder="Athletics, Perception">
                        </label>
                        <label class="full">
                            Skill Expertise
                            <input id="edit-skill-expertise" type="text" maxlength="500" placeholder="Stealth, Arcana">
                        </label>
                        <label>
                            Strength
                            <input id="edit-strength" type="number" min="3" max="18" required>
                        </label>
                        <label>
                            Dexterity
                            <input id="edit-dexterity" type="number" min="3" max="18" required>
                        </label>
                        <label>
                            Constitution
                            <input id="edit-constitution" type="number" min="3" max="18" required>
                        </label>
                        <label>
                            Intelligence
                            <input id="edit-intelligence" type="number" min="3" max="18" required>
                        </label>
                        <label>
                            Wisdom
                            <input id="edit-wisdom" type="number" min="3" max="18" required>
                        </label>
                        <label>
                            Charisma
                            <input id="edit-charisma" type="number" min="3" max="18" required>
                        </label>
                        <label class="full">
                            Personality Traits
                            <textarea id="edit-personality-traits" maxlength="1000"></textarea>
                        </label>
                        <label class="full">
                            Ideals
                            <textarea id="edit-ideals" maxlength="1000"></textarea>
                        </label>
                        <label class="full">
                            Goals
                            <textarea id="edit-goals" maxlength="1000"></textarea>
                        </label>
                        <label class="full">
                            Bonds
                            <textarea id="edit-bonds" maxlength="1000"></textarea>
                        </label>
                        <label class="full">
                            Flaws
                            <textarea id="edit-flaws" maxlength="1000"></textarea>
                        </label>
                        <label>
                            Age
                            <input id="edit-age" type="text" maxlength="255">
                        </label>
                        <label>
                            Height
                            <input id="edit-height" type="text" maxlength="255">
                        </label>
                        <label>
                            Weight
                            <input id="edit-weight" type="text" maxlength="255">
                        </label>
                        <label>
                            Eyes
                            <input id="edit-eyes" type="text" maxlength="255">
                        </label>
                        <label>
                            Hair
                            <input id="edit-hair" type="text" maxlength="255">
                        </label>
                        <label>
                            Skin
                            <input id="edit-skin" type="text" maxlength="255">
                        </label>
                        <label class="full">
                            Notes
                            <textarea id="edit-notes" maxlength="2000"></textarea>
                        </label>
                    </div>
                    <div class="modal-actions">
                        <button class="btn-soft" id="character-edit-cancel" type="button">Cancel</button>
                        <button class="btn" type="submit">Save changes</button>
                    </div>
                </form>
            </section>
        </div>

        <script id="roster-config" type="application/json">{!! json_encode([
            'species' => config('dnd.species', []),
            'classes' => config('dnd.classes', []),
            'class_details' => config('dnd.class_details', []),
            'backgrounds' => config('dnd.backgrounds', []),
            'alignments' => config('dnd.alignments', []),
            'origin_feats' => config('dnd.origin_feats', []),
            'advancement_methods' => config('dnd.advancement_methods', []),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

        <script>
            // Developer context: This boot block reads the server-provided roster metadata, grabs the page elements the script will reuse, and prepares the local draft keys and in-memory roster state.
            // Clear explanation: These lines connect the roster page script to the saved-character data and the visible controls on the page.
            const rosterConfig = JSON.parse(document.getElementById('roster-config').textContent);
            const countEl = document.getElementById('count');
            const searchStateEl = document.getElementById('search-state');
            const draftNotice = document.getElementById('draft-notice');
            const rosterNotice = document.getElementById('roster-notice');
            const searchInput = document.getElementById('roster-search');
            const charsEl = document.getElementById('characters');
            const characterEditModal = document.getElementById('character-edit-modal');
            const characterEditForm = document.getElementById('character-edit-form');
            const characterEditTitle = document.getElementById('character-edit-title');
            const editIdInput = document.getElementById('edit-character-id');
            const editClassSelect = document.getElementById('edit-class');
            const editSubclassSelect = document.getElementById('edit-subclass');
            // Developer context: This map keeps the browser storage keys in one place so the search draft and edit-popup draft do not drift apart.
            // Clear explanation: These labels tell the browser where to save the roster filter and edit drafts.
            const localDraftKeys = {
                filters: 'adventurers-ledger.roster-filters.v1',
                editor: 'adventurers-ledger.roster-editor.v1',
            };
            // Developer context: These variables hold the current roster records and autosave timing between API calls, filters, and popup edits.
            // Clear explanation: These lines remember the current roster list while the page stays open.
            let allCharacters = [];
            let rosterSaveTimer = null;

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

            // Developer context: Schedulerosterdraftsave updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function scheduleRosterDraftSave() {
                clearTimeout(rosterSaveTimer);
                rosterSaveTimer = window.setTimeout(persistRosterDrafts, 250);
            }

            // Developer context: Notice updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function notice(el, message, type) {
                el.textContent = message;
                el.className = `notice show ${type}`;
            }

            // Developer context: Clearnotice updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function clearNotice(el) {
                el.textContent = '';
                el.className = 'notice';
            }

            // Developer context: Showdraftrestorenotice updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function showDraftRestoreNotice(parts) {
                if (! draftNotice || ! Array.isArray(parts) || parts.length === 0) {
                    return;
                }

                notice(draftNotice, `Draft restored: ${parts.join(', ')}.`, 'success');
                window.setTimeout(() => clearNotice(draftNotice), 5000);
            }

            // Developer context: Escapehtml updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            // Developer context: Appearanceline updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function appearanceLine(character) {
                return [
                    character.age ? `Age ${character.age}` : '',
                    character.height ? `Height ${character.height}` : '',
                    character.weight ? `Weight ${character.weight}` : '',
                    character.eyes ? `Eyes ${character.eyes}` : '',
                    character.hair ? `Hair ${character.hair}` : '',
                    character.skin ? `Skin ${character.skin}` : '',
                ].filter(Boolean);
            }

            // Developer context: Filtercharacters updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function filterCharacters() {
                const term = searchInput.value.trim().toLowerCase();
                searchStateEl.textContent = term ? 'Filtered' : 'All';

                if (! term) {
                    renderCharacters(allCharacters);
                    return;
                }

                const filtered = allCharacters.filter((character) => {
                    const haystack = [
                        character.name,
                        character.species,
                        character.class,
                        character.subclass,
                        character.background,
                        character.alignment,
                        character.origin_feat,
                        character.advancement_method,
                        character.notes,
                        character.personality_traits,
                        character.ideals,
                        character.goals,
                        character.bonds,
                        character.flaws,
                        ...(Array.isArray(character.skill_proficiencies) ? character.skill_proficiencies : []),
                        ...(Array.isArray(character.skill_expertise) ? character.skill_expertise : []),
                        ...(Array.isArray(character.languages) ? character.languages : []),
                    ]
                        .filter(Boolean)
                        .join(' ')
                        .toLowerCase();

                    return haystack.includes(term);
                });

                renderCharacters(filtered);
            }

            // Developer context: Rendercharacters updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function renderCharacters(characters) {
                countEl.textContent = allCharacters.length;

                if (! characters.length) {
                    charsEl.innerHTML = allCharacters.length
                        ? `<div class="empty card"><span class="eyebrow">No matches</span><h3>Nothing fits that search yet.</h3><p>Try a broader search term or clear the filter to see the whole party again.</p></div>`
                        : `<div class="empty card"><span class="eyebrow">No party yet</span><h3>Your roster is empty.</h3><p>Create the first character from the builder or wizard and it will appear here automatically.</p></div>`;
                    return;
                }

                charsEl.innerHTML = characters.map((character) => {
                    const appearance = appearanceLine(character);

                    return `
                        <article class="char">
                            <div class="char-top">
                                <div>
                                    <div class="meta">Roster #${escapeHtml(character.roster_number)}</div>
                                    <h3>${escapeHtml(character.name)}</h3>
                                    <div class="meta">${escapeHtml(character.species || 'Unknown species')} / ${escapeHtml(character.class)} / ${escapeHtml(character.subclass || 'No subclass')} / ${escapeHtml(character.background || 'Unknown background')} / Level ${escapeHtml(character.level)}</div>
                                    ${[character.alignment, character.origin_feat].filter(Boolean).length ? `<div class="meta">${[character.alignment, character.origin_feat].filter(Boolean).map((value) => escapeHtml(value)).join(' / ')}</div>` : ''}
                                    ${character.advancement_method ? `<div class="meta">Advancement: ${escapeHtml(character.advancement_method)}</div>` : ''}
                                    ${Array.isArray(character.skill_proficiencies) && character.skill_proficiencies.length ? `<div class="meta">Skills: ${character.skill_proficiencies.map((value) => escapeHtml(value)).join(', ')}</div>` : ''}
                                    ${Array.isArray(character.skill_expertise) && character.skill_expertise.length ? `<div class="meta">Expertise: ${character.skill_expertise.map((value) => escapeHtml(value)).join(', ')}</div>` : ''}
                                    ${Array.isArray(character.languages) && character.languages.length ? `<div class="meta">Languages: ${character.languages.map((value) => escapeHtml(value)).join(', ')}</div>` : ''}
                                    ${character.personality_traits ? `<div class="meta">Trait: ${escapeHtml(character.personality_traits)}</div>` : ''}
                                    ${character.ideals ? `<div class="meta">Ideal: ${escapeHtml(character.ideals)}</div>` : ''}
                                    ${character.goals ? `<div class="meta">Goal: ${escapeHtml(character.goals)}</div>` : ''}
                                    ${character.bonds ? `<div class="meta">Bond: ${escapeHtml(character.bonds)}</div>` : ''}
                                    ${character.flaws ? `<div class="meta">Flaw: ${escapeHtml(character.flaws)}</div>` : ''}
                                    ${appearance.length ? `<div class="meta">${appearance.map((value) => escapeHtml(value)).join(' / ')}</div>` : ''}
                                </div>
                                <div class="actions-row">
                                    <button class="btn-soft" type="button" data-edit="${character.id}">Edit</button>
                                    <button class="btn-soft" type="button" data-delete="${character.id}">Delete</button>
                                </div>
                            </div>
                            <div class="stats">
                                <div class="stat"><span class="label">STR</span><span class="value">${escapeHtml(character.strength)}</span></div>
                                <div class="stat"><span class="label">DEX</span><span class="value">${escapeHtml(character.dexterity)}</span></div>
                                <div class="stat"><span class="label">CON</span><span class="value">${escapeHtml(character.constitution)}</span></div>
                                <div class="stat"><span class="label">INT</span><span class="value">${escapeHtml(character.intelligence)}</span></div>
                                <div class="stat"><span class="label">WIS</span><span class="value">${escapeHtml(character.wisdom)}</span></div>
                                <div class="stat"><span class="label">CHA</span><span class="value">${escapeHtml(character.charisma)}</span></div>
                            </div>
                            <p>${escapeHtml(character.notes ? character.notes : 'No notes yet.')}</p>
                        </article>
                    `;
                }).join('');
            }

            // Developer context: Populateselect updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function populateSelect(select, values, selectedValue = '', placeholder = 'Choose an option') {
                const options = [`<option value="">${escapeHtml(placeholder)}</option>`]
                    .concat((values || []).map((value) => `<option value="${escapeHtml(value)}"${value === selectedValue ? ' selected' : ''}>${escapeHtml(value)}</option>`));

                select.innerHTML = options.join('');
            }

            // Developer context: Populatesubclassoptions updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function populateSubclassOptions(selectedClass = '', selectedSubclass = '') {
                const subclasses = rosterConfig.class_details?.[selectedClass]?.subclasses || [];
                populateSelect(editSubclassSelect, subclasses, selectedSubclass, selectedClass ? 'Choose a subclass' : 'Choose a class first');
            }

            // Developer context: Commalist updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function commaList(values) {
                if (Array.isArray(values)) {
                    return values.join(', ');
                }

                return typeof values === 'string' ? values : '';
            }

            // Developer context: Fillcharactereditorform updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function fillCharacterEditorForm(values, characterId = '') {
                editIdInput.value = characterId || values.id || '';
                document.getElementById('edit-name').value = values.name || '';
                editClassSelect.value = values.class || '';
                populateSubclassOptions(editClassSelect.value, values.subclass || '');
                editSubclassSelect.value = values.subclass || '';
                document.getElementById('edit-advancement-method').value = values.advancement_method || 'Milestone';
                document.getElementById('edit-species').value = values.species || '';
                document.getElementById('edit-background').value = values.background || '';
                document.getElementById('edit-alignment').value = values.alignment || '';
                document.getElementById('edit-origin-feat').value = values.origin_feat || '';
                document.getElementById('edit-level').value = values.level || 1;
                document.getElementById('edit-languages').value = commaList(values.languages);
                document.getElementById('edit-skill-proficiencies').value = commaList(values.skill_proficiencies);
                document.getElementById('edit-skill-expertise').value = commaList(values.skill_expertise);
                document.getElementById('edit-strength').value = values.strength || 10;
                document.getElementById('edit-dexterity').value = values.dexterity || 10;
                document.getElementById('edit-constitution').value = values.constitution || 10;
                document.getElementById('edit-intelligence').value = values.intelligence || 10;
                document.getElementById('edit-wisdom').value = values.wisdom || 10;
                document.getElementById('edit-charisma').value = values.charisma || 10;
                document.getElementById('edit-personality-traits').value = values.personality_traits || '';
                document.getElementById('edit-ideals').value = values.ideals || '';
                document.getElementById('edit-goals').value = values.goals || '';
                document.getElementById('edit-bonds').value = values.bonds || '';
                document.getElementById('edit-flaws').value = values.flaws || '';
                document.getElementById('edit-age').value = values.age || '';
                document.getElementById('edit-height').value = values.height || '';
                document.getElementById('edit-weight').value = values.weight || '';
                document.getElementById('edit-eyes').value = values.eyes || '';
                document.getElementById('edit-hair').value = values.hair || '';
                document.getElementById('edit-skin').value = values.skin || '';
                document.getElementById('edit-notes').value = values.notes || '';
                characterEditTitle.textContent = `Edit ${values.name || 'character'}`;
            }

            // Developer context: Persistrosterdrafts updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function persistRosterDrafts() {
                if (searchInput.value.trim()) {
                    writeLocalDraft(localDraftKeys.filters, { search: searchInput.value });
                } else {
                    removeLocalDraft(localDraftKeys.filters);
                }

                if (characterEditModal.classList.contains('show') && editIdInput.value) {
                    writeLocalDraft(localDraftKeys.editor, {
                        id: editIdInput.value,
                        fields: characterPayloadFromForm(),
                    });
                } else {
                    removeLocalDraft(localDraftKeys.editor);
                }
            }

            // Developer context: Restorerosterfilterdraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function restoreRosterFilterDraft() {
                const draft = readLocalDraft(localDraftKeys.filters);
                if (draft && typeof draft.search === 'string') {
                    searchInput.value = draft.search;
                }
            }

            // Developer context: Restorecharactereditordraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function restoreCharacterEditorDraft() {
                if (characterEditModal.classList.contains('show')) {
                    return;
                }

                const draft = readLocalDraft(localDraftKeys.editor);
                if (! draft || ! draft.id || ! draft.fields || typeof draft.fields !== 'object') {
                    return;
                }

                const character = allCharacters.find((entry) => String(entry.id) === String(draft.id));
                if (! character) {
                    removeLocalDraft(localDraftKeys.editor);
                    return;
                }

                openCharacterEditor(draft.id);
                fillCharacterEditorForm(draft.fields, draft.id);
                scheduleRosterDraftSave();
            }

            // Developer context: This opens the edit popup with the selected character and fills every control from the saved roster data.
            // Clear explanation: This opens the character editor and loads that character into the popup.
            function openCharacterEditor(characterId) {
                const character = allCharacters.find((entry) => String(entry.id) === String(characterId));
                if (! character) {
                    notice(rosterNotice, 'That character could not be found for editing.', 'error');
                    return;
                }

                populateSelect(document.getElementById('edit-species'), rosterConfig.species, character.species, 'Choose a species');
                populateSelect(editClassSelect, rosterConfig.classes, character.class, 'Choose a class');
                populateSubclassOptions(character.class, character.subclass);
                populateSelect(document.getElementById('edit-advancement-method'), rosterConfig.advancement_methods, character.advancement_method || 'Milestone', 'Choose an advancement method');
                populateSelect(document.getElementById('edit-background'), rosterConfig.backgrounds, character.background, 'Choose a background');
                populateSelect(document.getElementById('edit-alignment'), rosterConfig.alignments, character.alignment || '', 'Optional alignment');
                populateSelect(document.getElementById('edit-origin-feat'), rosterConfig.origin_feats, character.origin_feat, 'Choose an origin feat');
                fillCharacterEditorForm(character, character.id);
                characterEditModal.classList.add('show');
                characterEditModal.setAttribute('aria-hidden', 'false');
                scheduleRosterDraftSave();
            }

            // Developer context: This resets the modal state so a stale editor draft does not leak into the next character the user opens.
            // Clear explanation: This closes the character editor and clears the temporary edit draft.
            function closeCharacterEditor() {
                characterEditForm.reset();
                editIdInput.value = '';
                characterEditModal.classList.remove('show');
                characterEditModal.setAttribute('aria-hidden', 'true');
                removeLocalDraft(localDraftKeys.editor);
            }

            // Developer context: Characterpayloadfromform updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function characterPayloadFromForm() {
                return {
                    name: document.getElementById('edit-name').value,
                    species: document.getElementById('edit-species').value,
                    class: editClassSelect.value,
                    subclass: editSubclassSelect.value,
                    background: document.getElementById('edit-background').value,
                    alignment: document.getElementById('edit-alignment').value,
                    origin_feat: document.getElementById('edit-origin-feat').value,
                    advancement_method: document.getElementById('edit-advancement-method').value,
                    languages: document.getElementById('edit-languages').value,
                    skill_proficiencies: document.getElementById('edit-skill-proficiencies').value,
                    skill_expertise: document.getElementById('edit-skill-expertise').value,
                    level: Number(document.getElementById('edit-level').value),
                    strength: Number(document.getElementById('edit-strength').value),
                    dexterity: Number(document.getElementById('edit-dexterity').value),
                    constitution: Number(document.getElementById('edit-constitution').value),
                    intelligence: Number(document.getElementById('edit-intelligence').value),
                    wisdom: Number(document.getElementById('edit-wisdom').value),
                    charisma: Number(document.getElementById('edit-charisma').value),
                    personality_traits: document.getElementById('edit-personality-traits').value,
                    ideals: document.getElementById('edit-ideals').value,
                    goals: document.getElementById('edit-goals').value,
                    bonds: document.getElementById('edit-bonds').value,
                    flaws: document.getElementById('edit-flaws').value,
                    age: document.getElementById('edit-age').value,
                    height: document.getElementById('edit-height').value,
                    weight: document.getElementById('edit-weight').value,
                    eyes: document.getElementById('edit-eyes').value,
                    hair: document.getElementById('edit-hair').value,
                    skin: document.getElementById('edit-skin').value,
                    notes: document.getElementById('edit-notes').value,
                };
            }

            async function loadCharacters() {
                try {
                    clearNotice(rosterNotice);
                    const response = await fetch('/api/characters', { headers: { Accept: 'application/json' } });
                    if (! response.ok) throw new Error();

                    const characters = await response.json();
                    allCharacters = Array.isArray(characters)
                        ? characters.map((character, index) => ({
                            ...character,
                            roster_number: index + 1,
                        }))
                        : [];

                    filterCharacters();
                    restoreCharacterEditorDraft();
                } catch {
                    notice(rosterNotice, 'The roster could not be loaded right now.', 'error');
                    countEl.textContent = '--';
                }
            }

            async function deleteCharacter(id) {
                if (! window.confirm('Delete this character from the roster?')) return;

                try {
                    const response = await fetch(`/api/characters/${id}`, { method: 'DELETE', headers: { Accept: 'application/json' } });
                    if (! response.ok) throw new Error();
                    notice(rosterNotice, 'Character removed from the roster.', 'success');
                    await loadCharacters();
                } catch {
                    notice(rosterNotice, 'The character could not be removed.', 'error');
                }
            }

            characterEditForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const characterId = editIdInput.value;

                if (! characterId) {
                    notice(rosterNotice, 'No character is selected for editing right now.', 'error');
                    return;
                }

                try {
                    const response = await fetch(`/api/characters/${characterId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(characterPayloadFromForm()),
                    });
                    const data = await response.json();

                    if (! response.ok) {
                        const firstError = Object.values(data.errors || {}).flat()[0] || data.message || 'The character could not be updated.';
                        throw new Error(firstError);
                    }

                    closeCharacterEditor();
                    notice(rosterNotice, data.message || 'Character updated.', 'success');
                    await loadCharacters();
                } catch (error) {
                    notice(rosterNotice, error.message || 'The character could not be updated.', 'error');
                }
            });

            editClassSelect.addEventListener('change', () => {
                populateSubclassOptions(editClassSelect.value);
                scheduleRosterDraftSave();
            });
            document.getElementById('character-edit-close').addEventListener('click', closeCharacterEditor);
            document.getElementById('character-edit-cancel').addEventListener('click', closeCharacterEditor);
            characterEditModal.addEventListener('click', (event) => {
                if (event.target === characterEditModal) {
                    closeCharacterEditor();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && characterEditModal.classList.contains('show')) {
                    closeCharacterEditor();
                }
            });
            document.getElementById('refresh').addEventListener('click', loadCharacters);
            searchInput.addEventListener('input', () => {
                filterCharacters();
                scheduleRosterDraftSave();
            });
            searchInput.addEventListener('change', scheduleRosterDraftSave);
            characterEditForm.addEventListener('input', scheduleRosterDraftSave);
            characterEditForm.addEventListener('change', scheduleRosterDraftSave);
            charsEl.addEventListener('click', (event) => {
                const editButton = event.target.closest('[data-edit]');
                if (editButton) {
                    openCharacterEditor(editButton.dataset.edit);
                    return;
                }

                const button = event.target.closest('[data-delete]');
                if (button) deleteCharacter(button.dataset.delete);
            });

            const restoredSections = [];
            if (readLocalDraft(localDraftKeys.filters)) {
                restoreRosterFilterDraft();
                restoredSections.push('search');
            }
            window.addEventListener('beforeunload', persistRosterDrafts);
            window.setInterval(persistRosterDrafts, 10000);
            loadCharacters();
            if (readLocalDraft(localDraftKeys.editor)) {
                restoredSections.push('edit popup');
            }
            showDraftRestoreNotice(restoredSections);
        </script>
    </body>
</html>
