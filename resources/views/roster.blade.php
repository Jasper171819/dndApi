<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Roster | Adventurer's Ledger</title>
        <style>
            :root{--bg:#130f0d;--panel:#221915;--soft:#31231d;--line:#4e372a;--text:#f7ead8;--muted:#d8bea0;--accent:#d58345;--accent2:#efba70}
            *{box-sizing:border-box} html{scroll-behavior:smooth}
            body{position:relative;min-height:100vh;margin:0;font-family:"Trebuchet MS","Segoe UI",sans-serif;color:var(--text);background:#100c0b}
            body::before{content:"";position:fixed;inset:0;z-index:-2;background:radial-gradient(circle at top left,rgba(213,131,69,.22),transparent 25%),linear-gradient(160deg,#100c0b,#1b1411 50%,#0e0b0a)}
            body::after{content:"";position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(0,0,0,.06),rgba(0,0,0,.18))}
            button,input{font:inherit} a{text-decoration:none;color:inherit}
            .wrap{position:relative;z-index:1;width:min(1180px,calc(100% - 2rem));margin:0 auto;padding:1rem 0 4rem}
            .topbar,.card,.panel,.char,.stat,.notice,.mini{border:1px solid var(--line);border-radius:24px;background:rgba(34,25,21,.9);box-shadow:0 20px 50px rgba(0,0,0,.28)}
            .topbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem 1.2rem;margin-top:1rem;border-radius:999px;background:rgba(19,15,13,.84);backdrop-filter:blur(10px)}
            .brand{display:flex;align-items:center;gap:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
            .mark{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#25170f;font-weight:900}
            .nav{display:flex;flex-wrap:wrap;gap:.6rem}
            .nav a,.btn,.btn-soft{padding:.8rem 1rem;border-radius:999px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);cursor:pointer;transition:.18s ease}
            .btn:hover,.btn-soft:hover,.nav a:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
            .btn{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#29180f;font-weight:700}
            main{display:grid;gap:1.4rem}
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
            @media (max-width:980px){.hero,.grid{grid-template-columns:1fr}}
            @media (max-width:720px){.toolbar{flex-direction:column;align-items:stretch}.toolbar-main{width:100%}.quick{grid-template-columns:1fr}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.topbar{border-radius:26px}}
            @media (max-width:640px){.wrap{width:min(100% - 1rem,100%)}.topbar,.card,.panel{padding:1.05rem}}
        </style>
    </head>
    <body>
        <div class="wrap">
            <header class="topbar">
                <a class="brand" href="{{ route('home') }}"><span class="mark">D20</span><span>Adventurer's Ledger</span></a>
                <nav class="nav">
                    <a href="{{ route('home') }}#forge">Builder</a>
                    <a href="{{ route('home') }}#dice">Dice</a>
                    <a href="{{ route('home') }}#wizard">Wizard</a>
                    <a href="{{ route('home') }}#library">Library</a>
                    <a href="{{ route('roster') }}">Roster</a>
                    <a href="{{ route('homebrew') }}">Homebrew</a>
                </nav>
            </header>

            <main>
                <section class="hero">
                    <article class="card">
                        <span class="eyebrow">Roster</span>
                        <h1>Keep the whole party in view.</h1>
                        <p>See every saved character in one place, search the group quickly, and tidy the roster without losing track of who is who. The number shown on each card is just that character's current roster slot on this page.</p>
                        <div class="hero-actions">
                            <a class="btn" href="{{ route('home') }}#forge">Create a character</a>
                            <a class="btn-soft" href="{{ route('home') }}#wizard">Open the wizard</a>
                        </div>
                    </article>

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

                <section class="panel">
                    <div class="toolbar">
                        <div>
                            <span class="eyebrow">Saved Characters</span>
                            <h2>Browse the party</h2>
                        </div>
                        <div class="toolbar-main">
                            <input id="roster-search" type="text" placeholder="Search by name, species, class, background, notes, or language">
                            <button class="btn-soft" id="refresh" type="button">Refresh</button>
                        </div>
                    </div>
                    <p class="tiny">Roster numbers are recalculated from the current list each time the page refreshes, so deleting a character closes the gap instead of leaving old slot numbers behind.</p>
                    <div class="notice" id="roster-notice"></div>
                    <div class="grid" id="characters"></div>
                </section>
            </main>
        </div>

        <script>
            const countEl = document.getElementById('count');
            const searchStateEl = document.getElementById('search-state');
            const rosterNotice = document.getElementById('roster-notice');
            const searchInput = document.getElementById('roster-search');
            const charsEl = document.getElementById('characters');
            let allCharacters = [];

            function notice(el, message, type) {
                el.textContent = message;
                el.className = `notice show ${type}`;
            }

            function clearNotice(el) {
                el.textContent = '';
                el.className = 'notice';
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

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
                        character.notes,
                        character.personality_traits,
                        character.ideals,
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
                                    ${Array.isArray(character.skill_proficiencies) && character.skill_proficiencies.length ? `<div class="meta">Skills: ${character.skill_proficiencies.map((value) => escapeHtml(value)).join(', ')}</div>` : ''}
                                    ${Array.isArray(character.skill_expertise) && character.skill_expertise.length ? `<div class="meta">Expertise: ${character.skill_expertise.map((value) => escapeHtml(value)).join(', ')}</div>` : ''}
                                    ${Array.isArray(character.languages) && character.languages.length ? `<div class="meta">Languages: ${character.languages.map((value) => escapeHtml(value)).join(', ')}</div>` : ''}
                                    ${character.personality_traits ? `<div class="meta">Trait: ${escapeHtml(character.personality_traits)}</div>` : ''}
                                    ${character.ideals ? `<div class="meta">Ideal: ${escapeHtml(character.ideals)}</div>` : ''}
                                    ${character.bonds ? `<div class="meta">Bond: ${escapeHtml(character.bonds)}</div>` : ''}
                                    ${character.flaws ? `<div class="meta">Flaw: ${escapeHtml(character.flaws)}</div>` : ''}
                                    ${appearance.length ? `<div class="meta">${appearance.map((value) => escapeHtml(value)).join(' / ')}</div>` : ''}
                                </div>
                                <button class="btn-soft" type="button" data-delete="${character.id}">Delete</button>
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

            document.getElementById('refresh').addEventListener('click', loadCharacters);
            searchInput.addEventListener('input', filterCharacters);
            charsEl.addEventListener('click', (event) => {
                const button = event.target.closest('[data-delete]');
                if (button) deleteCharacter(button.dataset.delete);
            });

            loadCharacters();
        </script>
    </body>
</html>
