{{-- Developer context: This Blade template renders the DM dashboard page; PHP provides local quick-reference data while the browser script loads shared API data for characters, monsters, homebrew, and dice. --}}
{{-- Clear explanation: This file is the Dungeon Master page for running sessions, tracking encounters, and keeping important table tools in one place. --}}
@php
    $dmPageData = [
        'conditions' => config('dnd.conditions', []),
        'damage_types' => config('dnd.damage_types', []),
        'roleplay_reference' => array_values(config('dnd.roleplay_reference', [])),
        'advancement_method_details' => config('dnd.advancement_method_details', []),
        'dc_guide' => [
            ['label' => 'Very Easy', 'value' => 5, 'note' => 'The task barely resists the attempt.'],
            ['label' => 'Easy', 'value' => 10, 'note' => 'A capable adventurer should manage this often.'],
            ['label' => 'Moderate', 'value' => 15, 'note' => 'This is the default "worth rolling for" difficulty.'],
            ['label' => 'Hard', 'value' => 20, 'note' => 'A strong plan, good bonus, or a lucky roll usually matters here.'],
            ['label' => 'Very Hard', 'value' => 25, 'note' => 'This should feel impressive when it succeeds.'],
            ['label' => 'Nearly Impossible', 'value' => 30, 'note' => 'Reserve this for truly extreme attempts.'],
        ],
        'skill_ability_map' => [
            'Acrobatics' => 'dexterity',
            'Animal Handling' => 'wisdom',
            'Arcana' => 'intelligence',
            'Athletics' => 'strength',
            'Deception' => 'charisma',
            'History' => 'intelligence',
            'Insight' => 'wisdom',
            'Intimidation' => 'charisma',
            'Investigation' => 'intelligence',
            'Medicine' => 'wisdom',
            'Nature' => 'intelligence',
            'Perception' => 'wisdom',
            'Performance' => 'charisma',
            'Persuasion' => 'charisma',
            'Religion' => 'intelligence',
            'Sleight of Hand' => 'dexterity',
            'Stealth' => 'dexterity',
            'Survival' => 'wisdom',
        ],
        'hit_die_by_class' => [
            'Barbarian' => 12,
            'Bard' => 8,
            'Cleric' => 8,
            'Druid' => 8,
            'Fighter' => 10,
            'Monk' => 8,
            'Paladin' => 10,
            'Ranger' => 10,
            'Rogue' => 8,
            'Sorcerer' => 6,
            'Warlock' => 8,
            'Wizard' => 6,
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>DM Desk | Adventurer's Ledger</title>
        <style>
            :root{--bg:#130f0d;--panel:#221915;--soft:#31231d;--line:#4e372a;--text:#f7ead8;--muted:#d8bea0;--accent:#d58345;--accent2:#efba70;--good:#476b3f;--danger:#7b4a4a;--info:#37556b}
            *{box-sizing:border-box} html{scroll-behavior:smooth;scroll-padding-top:7.5rem}
            body{position:relative;min-height:100vh;margin:0;font-family:"Trebuchet MS","Segoe UI",sans-serif;color:var(--text);background:#100c0b;overflow-x:hidden}
            body::before{content:"";position:fixed;inset:0;z-index:-2;background:radial-gradient(circle at top left,rgba(213,131,69,.22),transparent 25%),linear-gradient(160deg,#100c0b,#1b1411 50%,#0e0b0a)}
            body::after{content:"";position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(0,0,0,.06),rgba(0,0,0,.18))}
            button,input,textarea,select{font:inherit} a{text-decoration:none;color:inherit}
            .wrap{position:relative;z-index:1;width:min(1360px,calc(100% - 2rem));margin:0 auto;padding:1rem 0 4rem}
            .workspace{display:grid;grid-template-columns:210px minmax(0,1fr);gap:1rem;align-items:start}
            .topbar,.card,.panel,.notice,.mini,.entry,.combatant,.rule-card{border:1px solid var(--line);border-radius:24px;background:rgba(34,25,21,.9);box-shadow:0 20px 50px rgba(0,0,0,.28)}
            .topbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem 1.2rem;margin-top:1rem;border-radius:999px;background:rgba(19,15,13,.84);backdrop-filter:blur(10px)}
            .brand{display:flex;align-items:center;gap:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
            .mark{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#25170f;font-weight:900}
            .nav{display:flex;flex-wrap:wrap;gap:.6rem}
            .nav a,.btn,.btn-soft,.chip-button{padding:.8rem 1rem;border-radius:999px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);cursor:pointer;transition:.18s ease}
            .btn:hover,.btn-soft:hover,.nav a:hover,.chip-button:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
            .nav a.active{border-color:#8b623f;color:var(--text);background:rgba(255,255,255,.08)}
            .btn{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#29180f;font-weight:700}
            .page-rail{position:sticky;top:6.5rem;align-self:start;margin-top:2rem}
            .page-rail-card{padding:1rem}
            .page-rail-links{display:grid;gap:.6rem;margin-top:.8rem}
            .page-rail-links a{display:block;padding:.75rem .9rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);transition:.18s ease}
            .page-rail-links a:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
            main{display:grid;gap:1.4rem;grid-column:2}
            .hero{display:grid;grid-template-columns:1.15fr .85fr;gap:1rem;padding:2rem 0 0}
            .card,.panel,.rule-card{padding:1.5rem}
            .eyebrow{display:inline-block;margin-bottom:.8rem;color:var(--accent2);font-size:.78rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
            h1,h2,h3{margin:0;font-family:Georgia,"Times New Roman",serif;line-height:1.05}
            h1{font-size:clamp(2.5rem,5vw,4.2rem);max-width:11ch}
            h2{font-size:clamp(1.7rem,3vw,2.2rem)}
            h3{font-size:1.25rem}
            p{color:var(--muted);line-height:1.7}
            .hero-actions,.actions,.toolbar,.chip-list{display:flex;flex-wrap:wrap;gap:.75rem}
            .quick{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem;margin-top:1rem}
            .mini{padding:1rem;border-radius:18px;background:var(--soft)}
            .mini strong{display:block;margin-top:.35rem;font-size:1.15rem;color:var(--text)}
            .tiny{font-size:.92rem;color:rgba(247,234,216,.68)}
            .head{display:flex;justify-content:space-between;align-items:end;gap:1rem;margin-bottom:1rem}
            .section-note{margin-top:.35rem;margin-bottom:0}
            .notice{display:none;padding:1rem 1.1rem;border-radius:18px}
            .notice.show{display:block}
            .notice.error{color:#ffd9d9;border-color:#7b4a4a;background:rgba(123,74,74,.18)}
            .notice.success{color:#d7f0dc;border-color:#4d7556;background:rgba(77,117,86,.18)}
            .notice.info{color:#dbeaf7;border-color:#42657d;background:rgba(47,74,92,.18)}
            .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
            .full{grid-column:1 / -1}
            label{display:grid;gap:.35rem;color:var(--muted);font-size:.92rem}
            input,textarea,select{width:100%;padding:.85rem 1rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--text);outline:none}
            textarea{min-height:7rem;resize:vertical}
            input:focus,textarea:focus,select:focus{border-color:#9e754f}
            .dm-grid{display:grid;grid-template-columns:1fr .95fr;gap:1rem}
            .entry-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
            .entry,.combatant{padding:1rem;display:grid;gap:.75rem;background:rgba(255,255,255,.03)}
            .entry-top,.combatant-top{display:flex;justify-content:space-between;gap:.75rem;align-items:start}
            .meta{color:var(--muted);font-size:.93rem}
            .chip-list{gap:.45rem}
            .chip{padding:.35rem .6rem;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid var(--line);font-size:.82rem;color:var(--muted)}
            .chip.good{border-color:rgba(71,107,63,.9);background:rgba(43,76,49,.5);color:#ecf8ea}
            .chip.enemy{border-color:rgba(123,74,74,.95);background:rgba(123,74,74,.28);color:#ffe4e4}
            .chip.ally{border-color:rgba(68,105,123,.95);background:rgba(45,69,83,.35);color:#e0f1ff}
            .toolbar{justify-content:space-between;align-items:end}
            .toolbar-main{display:grid;grid-template-columns:minmax(220px,1fr) 170px auto;gap:.75rem;flex:1}
            .combatant-list,.dice-log,.rule-grid{display:grid;gap:1rem}
            .combatant.active{border-color:#8b623f;box-shadow:0 16px 40px rgba(0,0,0,.35),0 0 0 1px rgba(239,186,112,.18) inset}
            .combatant-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}
            .combatant-grid label,.inline-grid label{font-size:.86rem}
            .inline-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}
            .stack{display:grid;gap:1rem}
            .wizard-grid{display:grid;grid-template-columns:1.08fr .92fr;gap:1rem}
            .wizard-log{display:grid;gap:.8rem;max-height:28rem;overflow:auto;padding-right:.25rem}
            .wizard-message{padding:1rem;border-radius:18px;border:1px solid var(--line);background:rgba(255,255,255,.03)}
            .wizard-message.user{background:rgba(68,105,123,.18);border-color:rgba(68,105,123,.75)}
            .wizard-message.assistant{background:rgba(255,255,255,.03)}
            .wizard-message strong{display:block;margin-bottom:.45rem;color:var(--text)}
            .wizard-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:.75rem;margin-top:1rem}
            .records-list{display:grid;gap:.75rem}
            .snapshot-box{display:grid;gap:.8rem}
            .snapshot-fields{display:grid;gap:.6rem}
            .snapshot-field{padding:.8rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.03)}
            .snapshot-field strong{display:block;margin-bottom:.2rem;color:var(--text)}
            .snapshot-actions{display:flex;flex-wrap:wrap;gap:.6rem}
            .combatant-actions{display:flex;flex-wrap:wrap;gap:.55rem}
            .mini-button{padding:.6rem .8rem;border-radius:14px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--muted);cursor:pointer}
            .mini-button:hover{border-color:#8b623f;color:var(--text)}
            .mini-button.danger{border-color:rgba(123,74,74,.95);color:#ffd9d9}
            .mini-button.good{border-color:rgba(71,107,63,.95);color:#d7f0dc}
            .mini-button.info{border-color:rgba(68,105,123,.95);color:#dbeaf7}
            .dice-buttons{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}
            .dice-form{display:grid;grid-template-columns:minmax(0,1fr) 190px minmax(0,1fr) auto;gap:.75rem;margin-top:1rem}
            .log-entry{padding:1rem;border-radius:18px;background:var(--soft);border:1px solid var(--line)}
            .log-entry p,.rule-card p{margin:.35rem 0 0}
            .rule-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
            .rule-card ul{margin:.75rem 0 0;padding-left:1rem;color:var(--muted)}
            .rule-card li{margin:.3rem 0}
            .empty{padding:2.25rem;text-align:center;border-radius:18px;border:1px dashed var(--line);color:var(--muted);background:rgba(255,255,255,.02)}
            @media (max-width:1120px){.workspace{grid-template-columns:180px minmax(0,1fr)}}
            @media (max-width:980px){.workspace,.hero,.dm-grid,.entry-grid,.grid,.rule-grid,.wizard-grid{grid-template-columns:1fr}.toolbar-main,.dice-form,.wizard-form{grid-template-columns:1fr}.topbar{position:static;border-radius:28px;align-items:stretch}.nav{justify-content:center}.page-rail{position:static;margin-top:0}.page-rail-links{grid-template-columns:repeat(2,minmax(0,1fr))}}
            @media (max-width:720px){.quick,.combatant-grid,.inline-grid,.dice-buttons{grid-template-columns:1fr}.topbar{border-radius:26px;padding:1rem}.brand{justify-content:center}.nav a{flex:1 1 calc(50% - .6rem);text-align:center}.head{flex-direction:column;align-items:start}}
            @media (max-width:640px){.wrap{width:min(100% - 1rem,100%)}.topbar,.card,.panel,.rule-card{padding:1.05rem}}
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
                {{-- Developer context: This rail keeps the DM page's section jumps separate from the actual session tools. --}}
                {{-- Clear explanation: This side block is the quick menu for moving around the DM page. --}}
                <aside class="page-rail">
                    <section class="panel page-rail-card">
                        <span class="eyebrow">On This Page</span>
                        <nav class="page-rail-links">
                            <a href="#overview">Overview</a>
                            <a href="#dm-wizard">DM Wizard</a>
                            <a href="#session-board">Session Board</a>
                            <a href="#encounter-tracker">Encounter</a>
                            <a href="#dm-dice">Dice</a>
                            <a href="#party-desk">Party</a>
                            <a href="#monster-vault">Monsters</a>
                            <a href="#quick-rules">Quick Rules</a>
                            <a href="#homebrew-reference">Table-ready Brew</a>
                        </nav>
                    </section>
                </aside>

                <main>
                    {{-- Developer context: This overview section introduces the DM desk and points people toward the session tools they use most often. --}}
                    {{-- Clear explanation: This block is the introduction to the DM page. --}}
                    <section class="hero" id="overview">
                        <article class="card">
                            <span class="eyebrow">DM Desk</span>
                            <h1>Run the table without juggling six tabs.</h1>
                            <p>Keep session notes, encounter order, dice, monsters, party reminders, table-ready homebrew, and a separate DM wizard in one place while the builder and player wizard stay on the player side.</p>
                            <p class="tiny">This page autosaves its own draft data in this browser, so a weak connection or reload does not have to wipe the session board, encounter tracker, or DM wizard draft.</p>
                            <div class="notice" id="draft-notice"></div>
                            <div class="hero-actions">
                                <a class="btn" href="#dm-wizard">Open DM wizard</a>
                                <a class="btn" href="#encounter-tracker">Open encounter tracker</a>
                                <a class="btn-soft" href="#monster-vault">Browse monsters</a>
                                <a class="btn-soft" href="{{ route('home') }}">Back to builder</a>
                            </div>
                        </article>

                        <aside class="card">
                            <span class="eyebrow">At a Glance</span>
                            <div class="quick">
                                <div class="mini">Party members<strong id="glance-party-count">0</strong></div>
                                <div class="mini">Monsters loaded<strong id="glance-monster-count">0</strong></div>
                                <div class="mini">DM records<strong id="glance-record-count">0</strong></div>
                                <div class="mini">Encounter round<strong id="glance-round">1</strong></div>
                                <div class="mini">Active turn<strong id="glance-active">No one yet</strong></div>
                            </div>
                        </aside>
                    </section>

                    {{-- Developer context: This section holds the separate DM-only wizard together with its quick actions, snapshot, and saved DM records. --}}
                    {{-- Clear explanation: This block is the DM wizard area. --}}
                    <section class="panel" id="dm-wizard">
                        <div class="head">
                            <div>
                                <span class="eyebrow">DM Wizard</span>
                                <h2>Build table-ready DM material without touching the player wizard</h2>
                                <p class="section-note tiny">Use `new npc`, `new scene`, `new quest`, `new location`, `new encounter`, or `new loot`. Saved DM records stay separate from homebrew until you explicitly export them.</p>
                            </div>
                        </div>

                        <div class="notice" id="wizard-notice"></div>

                        <div class="wizard-grid">
                            <article class="card">
                                <div class="wizard-log" id="wizard-log"></div>

                                <form class="wizard-form" id="wizard-form">
                                    <label class="full">
                                        DM Wizard input
                                        <input id="wizard-input" type="text" maxlength="500" placeholder="Try `new npc`, `show monster goblin`, `roll 2d6+3`, or `list dm records`">
                                    </label>
                                    <button class="btn" type="submit">Send</button>
                                </form>
                            </article>

                            <aside class="stack">
                                <section class="card">
                                    <span class="eyebrow">Quick Actions</span>
                                    <div class="chip-list" id="wizard-quick-actions"></div>
                                </section>

                                <section class="card">
                                    <span class="eyebrow">Draft Snapshot</span>
                                    <div class="snapshot-box" id="wizard-snapshot"></div>
                                </section>

                                <section class="card">
                                    <span class="eyebrow">Saved DM Records</span>
                                    <div class="records-list" id="dm-records"></div>
                                </section>
                            </aside>
                        </div>
                    </section>

                    <section class="panel" id="session-board">
                        <div class="head">
                            <div>
                                <span class="eyebrow">Session Board</span>
                                <h2>Keep the whole scene visible</h2>
                                <p class="section-note tiny">Use this for what the party is doing right now, what is still unresolved, and what pressure is building in the background.</p>
                            </div>
                            <div class="actions">
                                <button class="btn-soft" id="reset-session" type="button">Reset notes</button>
                                <button class="btn-soft" id="clear-drafts" type="button">Clear local draft</button>
                            </div>
                        </div>

                        <form class="stack" id="session-form">
                            <div class="grid">
                                <label>
                                    Session title
                                    <input id="session-title" type="text" maxlength="120" placeholder="Session 14 - Into the Drowned Archive">
                                </label>
                                <label>
                                    Chapter or arc
                                    <input id="session-chapter" type="text" maxlength="120" placeholder="The Flooded Keys">
                                </label>
                                <label>
                                    Current location
                                    <input id="session-location" type="text" maxlength="120" placeholder="Moonwell Causeway, western vault, drowned watchtower...">
                                </label>
                                <label>
                                    Current threat
                                    <input id="session-threat" type="text" maxlength="140" placeholder="Aboleth influence, collapsing bridge, rival crew, dwindling air...">
                                </label>
                                <label>
                                    Table tone
                                    <input id="session-tone" type="text" maxlength="120" placeholder="Tense investigation, ruin crawl, desperate escape...">
                                </label>
                                <label>
                                    Rest outlook
                                    <select id="session-rest">
                                        <option value="">Pick the likely rest pressure</option>
                                        <option>Safe to long rest</option>
                                        <option>Short rest likely</option>
                                        <option>Rest is risky</option>
                                        <option>No rest until the scene breaks</option>
                                    </select>
                                </label>
                                <label>
                                    Current day
                                    <input id="session-day" type="text" maxlength="40" placeholder="Day 9, 3 Eleasis, second watch...">
                                </label>
                                <label>
                                    Current watch
                                    <select id="session-watch">
                                        <option value="">Pick the current watch</option>
                                        <option>Dawn</option>
                                        <option>Morning</option>
                                        <option>Midday</option>
                                        <option>Afternoon</option>
                                        <option>Dusk</option>
                                        <option>Night</option>
                                        <option>Midnight</option>
                                    </select>
                                </label>
                                <label>
                                    Pressure clock size
                                    <input id="session-clock-total" type="number" min="1" max="12" placeholder="6">
                                </label>
                                <label>
                                    Pressure clock filled
                                    <input id="session-clock-filled" type="number" min="0" max="12" placeholder="2">
                                </label>
                                <label class="full">
                                    Party objective
                                    <textarea id="session-objective" maxlength="800" placeholder="What is the group trying to achieve right now, before the next break in play?"></textarea>
                                </label>
                                <label class="full">
                                    Active scene notes
                                    <textarea id="session-scene" maxlength="1200" placeholder="What just happened, what is visible, and what is likely to matter in the next few minutes?"></textarea>
                                </label>
                                <label class="full">
                                    Open threads and consequences
                                    <textarea id="session-threads" maxlength="1200" placeholder="Who is waiting for an answer, what debt is still hanging, what danger is getting closer?"></textarea>
                                </label>
                                <label class="full">
                                    NPC, clue, and loot notes
                                    <textarea id="session-npcs" maxlength="1200" placeholder="Names, voices, motives, secrets, clues, treasure, faction reactions..."></textarea>
                                </label>
                                <label class="full">
                                    Table notes
                                    <textarea id="session-table-notes" maxlength="1200" placeholder="Rules clarifications, pacing reminders, who asked for what, or anything else you need close at hand."></textarea>
                                </label>
                            </div>
                        </form>
                    </section>

                    <section class="panel" id="encounter-tracker">
                        <div class="head">
                            <div>
                                <span class="eyebrow">Encounter Tracker</span>
                                <h2>Keep turn order and hit points honest</h2>
                                <p class="section-note tiny">Party members can be added from saved characters. Monsters are added from the Monster Vault below. Damage spends temp HP first, and temp HP uses the highest value instead of stacking.</p>
                            </div>
                            <div class="actions">
                                <button class="btn-soft" id="roll-missing-init" type="button">Roll missing initiative</button>
                                <button class="btn-soft" id="next-turn" type="button">Next turn</button>
                                <button class="btn-soft" id="clear-encounter" type="button">Clear encounter</button>
                            </div>
                        </div>
                        <div class="quick">
                            <div class="mini">Combatants<strong id="encounter-count">0</strong></div>
                            <div class="mini">Enemies<strong id="encounter-enemies">0</strong></div>
                            <div class="mini">Round<strong id="encounter-round">1</strong></div>
                            <div class="mini">Up next<strong id="encounter-active">No one yet</strong></div>
                        </div>
                        <div class="dm-grid" style="margin-top:1rem">
                            <div class="stack">
                                <div class="rule-card">
                                    <h3>Add from the saved party</h3>
                                    <p class="tiny">Characters added here use their saved level, initiative bonus, skills, and an estimated max HP so you can still adjust them mid-fight.</p>
                                    <div class="grid">
                                        <label class="full">
                                            Saved character
                                            <select id="encounter-character-select">
                                                <option value="">Load the saved roster first</option>
                                            </select>
                                        </label>
                                    </div>
                                    <div class="actions" style="margin-top:1rem">
                                        <button class="btn" id="add-character-to-encounter" type="button">Add party member</button>
                                    </div>
                                </div>

                                <div class="rule-card">
                                    <h3>Add a custom combatant</h3>
                                    <p class="tiny">Use this for allies, hazards, summons, named NPCs, or anything you do not want to add from the party or monster list.</p>
                                    <form class="stack" id="custom-combatant-form">
                                        <div class="grid">
                                            <label>
                                                Name
                                                <input id="custom-name" type="text" maxlength="120" placeholder="Bridge Ogre, Captain Ysolde, Falling Debris..." required>
                                            </label>
                                            <label>
                                                Side
                                                <select id="custom-side">
                                                    <option value="enemy">Enemy</option>
                                                    <option value="party">Party</option>
                                                    <option value="ally">Ally</option>
                                                    <option value="npc">NPC</option>
                                                    <option value="hazard">Hazard</option>
                                                </select>
                                            </label>
                                            <label>
                                                Initiative bonus
                                                <input id="custom-init-bonus" type="number" min="-10" max="20" value="0">
                                            </label>
                                            <label>
                                                Armor Class
                                                <input id="custom-ac" type="text" maxlength="40" placeholder="15">
                                            </label>
                                            <label>
                                                Current HP
                                                <input id="custom-current-hp" type="number" min="0" value="1">
                                            </label>
                                            <label>
                                                Max HP
                                                <input id="custom-max-hp" type="number" min="1" value="1">
                                            </label>
                                            <label>
                                                Temp HP
                                                <input id="custom-temp-hp" type="number" min="0" value="0">
                                            </label>
                                            <label>
                                                Conditions
                                                <input id="custom-conditions" type="text" list="condition-options" maxlength="200" placeholder="Prone, Poisoned">
                                            </label>
                                            <label class="full">
                                                Notes
                                                <input id="custom-note" type="text" maxlength="240" placeholder="What matters tactically or narratively about this combatant?">
                                            </label>
                                        </div>
                                        <div class="actions">
                                            <button class="btn" type="submit">Add custom combatant</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="stack">
                                <div class="notice" id="encounter-notice"></div>
                                <div class="combatant-list" id="combatant-list">
                                    <div class="empty">No one is in the encounter yet. Add a party member, a monster from the vault, or a custom combatant to start tracking the scene.</div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="panel" id="dm-dice">
                        <div class="head">
                            <div>
                                <span class="eyebrow">DM Dice</span>
                                <h2>Roll through the same validated dice path</h2>
                                <p class="section-note tiny">These rolls use the app's real dice API, so the DM desk stays consistent with the builder and wizard.</p>
                            </div>
                        </div>
                        <div class="notice" id="dice-notice"></div>
                        <div class="dice-buttons">
                            <button class="btn-soft" type="button" data-dice-expression="1d20">d20</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d20" data-dice-mode="advantage">d20 advantage</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d20" data-dice-mode="disadvantage">d20 disadvantage</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d100">d100</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d4">d4</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d6">d6</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d8">d8</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d10">d10</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d12">d12</button>
                            <button class="btn-soft" type="button" data-dice-expression="2d6+3">2d6+3</button>
                            <button class="btn-soft" type="button" data-dice-expression="4d6">4d6</button>
                            <button class="btn-soft" type="button" data-dice-expression="8d6">8d6</button>
                        </div>

                        <form class="dice-form" id="dice-form">
                            <input id="dice-expression" type="text" maxlength="50" placeholder="1d20+7, 6d6, 2d8+4...">
                            <select id="dice-mode">
                                <option value="">Normal</option>
                                <option value="advantage">Advantage</option>
                                <option value="disadvantage">Disadvantage</option>
                            </select>
                            <input id="dice-context" type="text" maxlength="140" placeholder="Goblin ambush, falling portcullis, hidden perception check...">
                            <button class="btn" type="submit">Roll</button>
                        </form>

                        <div class="dice-log" id="dice-log" style="margin-top:1rem">
                            <div class="empty">Rolls will appear here with the expression, total, and detail string.</div>
                        </div>
                    </section>
                    <section class="panel" id="party-desk">
                        <div class="toolbar">
                            <div>
                                <span class="eyebrow">Party Desk</span>
                                <h2>Quick reference for saved characters</h2>
                            </div>
                            <div class="toolbar-main">
                                <input id="party-search" type="text" placeholder="Search by name, species, class, background, alignment, languages, skills, or goals">
                                <select id="party-sort">
                                    <option value="name">Sort by name</option>
                                    <option value="level">Sort by level</option>
                                    <option value="class">Sort by class</option>
                                </select>
                                <button class="btn-soft" id="refresh-party" type="button">Refresh</button>
                            </div>
                        </div>
                        <p class="tiny">This is meant for fast DM scanning. Editing still belongs on the main roster page.</p>
                        <div class="entry-grid" id="party-cards">
                            <div class="empty">The DM desk is loading the saved party.</div>
                        </div>
                    </section>

                    <section class="panel" id="monster-vault">
                        <div class="toolbar">
                            <div>
                                <span class="eyebrow">Monster Vault</span>
                                <h2>Search monsters and drop them into initiative</h2>
                            </div>
                            <div class="toolbar-main">
                                <input id="monster-search" type="text" placeholder="Search by monster name, type, summary, trait, action, or language">
                                <select id="monster-cr-filter">
                                    <option value="">All challenge ratings</option>
                                </select>
                                <button class="btn-soft" id="refresh-monsters" type="button">Refresh</button>
                            </div>
                        </div>
                        <p class="tiny">Adding a monster copies its AC, HP, and initiative bonus into the encounter tracker. You still roll its actual initiative from the card or with the "roll missing initiative" button.</p>
                        <div class="entry-grid" id="monster-cards">
                            <div class="empty">The DM desk is loading the local monster compendium.</div>
                        </div>
                    </section>

                    <section class="panel" id="quick-rules">
                        <div class="head">
                            <div>
                                <span class="eyebrow">Quick Rules</span>
                                <h2>Keep the common reminders on screen</h2>
                            </div>
                        </div>
                        <div class="rule-grid">
                            <article class="rule-card">
                                <h3>Difficulty ladder</h3>
                                <ul>
                                    @foreach ($dmPageData['dc_guide'] as $entry)
                                        <li><strong>{{ $entry['label'] }} (DC {{ $entry['value'] }})</strong>: {{ $entry['note'] }}</li>
                                    @endforeach
                                </ul>
                            </article>

                            <article class="rule-card">
                                <h3>Roleplay and social scenes</h3>
                                <ul>
                                    @foreach ($dmPageData['roleplay_reference'] as $entry)
                                        <li><strong>{{ $entry['title'] ?? 'Note' }}</strong>: {{ $entry['summary'] ?? '' }}</li>
                                    @endforeach
                                </ul>
                            </article>

                            <article class="rule-card">
                                <h3>Conditions to keep handy</h3>
                                <div class="chip-list">
                                    @foreach ($dmPageData['conditions'] as $condition)
                                        <span class="chip">{{ $condition }}</span>
                                    @endforeach
                                </div>
                            </article>

                            <article class="rule-card">
                                <h3>Damage types</h3>
                                <div class="chip-list">
                                    @foreach ($dmPageData['damage_types'] as $type)
                                        <span class="chip">{{ $type }}</span>
                                    @endforeach
                                </div>
                            </article>

                            <article class="rule-card">
                                <h3>Advancement reminders</h3>
                                <ul>
                                    @foreach ($dmPageData['advancement_method_details'] as $method => $detail)
                                        <li><strong>{{ $method }}</strong>: {{ $detail['play_note'] ?? $detail['summary'] ?? '' }}</li>
                                    @endforeach
                                </ul>
                            </article>

                            <article class="rule-card">
                                <h3>Tracker notes</h3>
                                <ul>
                                    <li><strong>Temp HP</strong>: Damage spends temp HP first, and a new temp HP value only replaces a lower one.</li>
                                    <li><strong>Initiative</strong>: Monster cards add the stat-block bonus, but the turn order itself should still be rolled.</li>
                                    <li><strong>Party import</strong>: Saved characters keep their class, level, languages, and skills when you add them to the tracker.</li>
                                </ul>
                            </article>
                        </div>
                    </section>

                    <section class="panel" id="homebrew-reference">
                        <div class="toolbar">
                            <div>
                                <span class="eyebrow">Table-ready Brew</span>
                                <h2>Bring the custom material that is ready to use</h2>
                            </div>
                            <div class="toolbar-main">
                                <input id="homebrew-search" type="text" placeholder="Search by name, summary, details, source notes, or tags">
                                <select id="homebrew-status-filter">
                                    <option value="">Table-ready and playtest</option>
                                    <option value="table-ready">Table-ready only</option>
                                    <option value="playtest">Playtest only</option>
                                    <option value="draft">Drafts too</option>
                                </select>
                                <a class="btn-soft" href="{{ route('homebrew') }}">Open workshop</a>
                            </div>
                        </div>
                        <p class="tiny">This section is still read-only here. It is just the DM-facing view of custom material that may matter in play.</p>
                        <div class="entry-grid" id="homebrew-cards">
                            <div class="empty">The DM desk is loading the saved homebrew entries.</div>
                        </div>
                    </section>
                </main>
            </div>
        </div>

        <datalist id="condition-options">
            @foreach ($dmPageData['conditions'] as $condition)
                <option value="{{ $condition }}"></option>
            @endforeach
        </datalist>

        <script id="dm-page-data" type="application/json">@json($dmPageData)</script>
        <script>
            const dmPageData = JSON.parse(document.getElementById('dm-page-data').textContent);
            const endpoints = {
                characters: '/api/characters',
                monsters: '/api/compendium/monsters',
                homebrew: '/api/homebrew',
                dmRecords: '/api/dm-records',
                dmWizard: '/api/dm-wizard/message',
                rollDice: '/api/roll-dice',
            };

            const defaultState = {
                session: {
                    title: '',
                    chapter: '',
                    location: '',
                    threat: '',
                    tone: '',
                    rest: '',
                    day: '',
                    watch: '',
                    clock_total: '',
                    clock_filled: '',
                    objective: '',
                    scene: '',
                    threads: '',
                    npcs: '',
                    table_notes: '',
                },
                encounter: {
                    round: 1,
                    activeId: null,
                    combatants: [],
                },
                dice: {
                    expression: '',
                    mode: '',
                    context: '',
                    log: [],
                },
                wizard: {
                    input: '',
                    log: [],
                    state: {},
                    quickActions: ['new npc', 'new scene', 'new encounter', 'list dm records', 'help'],
                    snapshot: null,
                },
                filters: {
                    partySearch: '',
                    partySort: 'name',
                    monsterSearch: '',
                    monsterCr: '',
                    homebrewSearch: '',
                    homebrewStatus: '',
                },
            };

            const storageKey = 'dm-page-draft-v2';
            const partyCards = document.getElementById('party-cards');
            const monsterCards = document.getElementById('monster-cards');
            const homebrewCards = document.getElementById('homebrew-cards');
            const dmRecordsList = document.getElementById('dm-records');
            const combatantList = document.getElementById('combatant-list');
            const diceLog = document.getElementById('dice-log');
            const draftNotice = document.getElementById('draft-notice');
            const wizardNotice = document.getElementById('wizard-notice');
            const encounterNotice = document.getElementById('encounter-notice');
            const diceNotice = document.getElementById('dice-notice');
            const sessionForm = document.getElementById('session-form');
            const wizardForm = document.getElementById('wizard-form');
            const wizardInput = document.getElementById('wizard-input');
            const wizardLog = document.getElementById('wizard-log');
            const wizardQuickActions = document.getElementById('wizard-quick-actions');
            const wizardSnapshot = document.getElementById('wizard-snapshot');
            const customCombatantForm = document.getElementById('custom-combatant-form');
            const encounterCharacterSelect = document.getElementById('encounter-character-select');
            const partySearch = document.getElementById('party-search');
            const partySort = document.getElementById('party-sort');
            const monsterSearch = document.getElementById('monster-search');
            const monsterCrFilter = document.getElementById('monster-cr-filter');
            const homebrewSearch = document.getElementById('homebrew-search');
            const homebrewStatusFilter = document.getElementById('homebrew-status-filter');
            const diceExpression = document.getElementById('dice-expression');
            const diceMode = document.getElementById('dice-mode');
            const diceContext = document.getElementById('dice-context');

            let restoredDraftSections = [];
            let state = loadState();
            let characters = [];
            let monsters = [];
            let homebrewEntries = [];
            let dmRecords = [];

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            // Developer context: This rebuilds the DM page draft from browser storage while keeping the saved shape aligned with the default state.
            // Clear explanation: This loads the local DM draft after a reload.
            function loadState() {
                try {
                    const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
                    if (! saved || typeof saved !== 'object') {
                        return structuredClone(defaultState);
                    }

                    restoredDraftSections = restoredSectionsFromState(saved);

                    const merged = structuredClone(defaultState);
                    merged.session = { ...merged.session, ...(saved.session || {}) };
                    merged.encounter = {
                        ...merged.encounter,
                        ...(saved.encounter || {}),
                        combatants: Array.isArray(saved.encounter?.combatants)
                            ? saved.encounter.combatants.map(normalizeCombatant)
                            : [],
                    };
                    merged.dice = {
                        ...merged.dice,
                        ...(saved.dice || {}),
                        log: Array.isArray(saved.dice?.log) ? saved.dice.log.slice(0, 20) : [],
                    };
                    merged.wizard = {
                        ...merged.wizard,
                        ...(saved.wizard || {}),
                        log: Array.isArray(saved.wizard?.log) ? saved.wizard.log.slice(-30) : [],
                        state: saved.wizard?.state && typeof saved.wizard.state === 'object' ? saved.wizard.state : {},
                        quickActions: Array.isArray(saved.wizard?.quickActions)
                            ? saved.wizard.quickActions.slice(0, 12)
                            : merged.wizard.quickActions,
                        snapshot: saved.wizard?.snapshot && typeof saved.wizard.snapshot === 'object'
                            ? saved.wizard.snapshot
                            : null,
                    };
                    merged.filters = { ...merged.filters, ...(saved.filters || {}) };

                    return merged;
                } catch (error) {
                    return structuredClone(defaultState);
                }
            }

            // Developer context: This inspects the saved draft and turns it into human-readable section names for the restore notice.
            // Clear explanation: This figures out which parts of the DM page were restored from the local draft.
            function restoredSectionsFromState(saved) {
                const sections = [];
                const hasText = (value) => typeof value === 'string' && value.trim();

                if (
                    saved.session
                    && [
                        saved.session.title,
                        saved.session.campaign,
                        saved.session.scene,
                        saved.session.agenda,
                        saved.session.table_notes,
                    ].some(hasText)
                ) {
                    sections.push('session board');
                }

                if (
                    saved.encounter
                    && (
                        (Array.isArray(saved.encounter.combatants) && saved.encounter.combatants.length > 0)
                        || Number(saved.encounter.round || 1) > 1
                        || saved.encounter.activeId
                    )
                ) {
                    sections.push('encounter tracker');
                }

                if (
                    saved.dice
                    && (
                        hasText(saved.dice.expression)
                        || hasText(saved.dice.mode)
                        || hasText(saved.dice.context)
                        || (Array.isArray(saved.dice.log) && saved.dice.log.length > 0)
                    )
                ) {
                    sections.push('dice tray');
                }

                if (
                    saved.wizard
                    && (
                        hasText(saved.wizard.input)
                        || (Array.isArray(saved.wizard.log) && saved.wizard.log.length > 0)
                        || (saved.wizard.state && typeof saved.wizard.state === 'object' && Object.keys(saved.wizard.state).length > 0)
                        || saved.wizard.snapshot
                    )
                ) {
                    sections.push('wizard');
                }

                if (
                    saved.filters
                    && Object.values(saved.filters).some((value) => hasText(String(value ?? '')))
                ) {
                    sections.push('filters');
                }

                return sections;
            }

            function normalizeCombatant(entry) {
                return {
                    id: String(entry?.id || crypto.randomUUID()),
                    order: Number.isFinite(Number(entry?.order)) ? Number(entry.order) : Date.now(),
                    source: entry?.source || 'custom',
                    sourceLabel: entry?.sourceLabel || 'Custom',
                    name: String(entry?.name || 'Unnamed combatant'),
                    side: entry?.side || 'enemy',
                    initiative: Number.isFinite(Number(entry?.initiative)) ? Number(entry.initiative) : null,
                    initiative_bonus: Number.isFinite(Number(entry?.initiative_bonus)) ? Number(entry.initiative_bonus) : 0,
                    ac: String(entry?.ac ?? ''),
                    current_hp: Number.isFinite(Number(entry?.current_hp)) ? Number(entry.current_hp) : 0,
                    max_hp: Number.isFinite(Number(entry?.max_hp)) ? Number(entry.max_hp) : 0,
                    temp_hp: Number.isFinite(Number(entry?.temp_hp)) ? Number(entry.temp_hp) : 0,
                    conditions: Array.isArray(entry?.conditions)
                        ? entry.conditions.filter(Boolean)
                        : String(entry?.conditions || '').split(',').map((item) => item.trim()).filter(Boolean),
                    note: String(entry?.note || ''),
                    initiative_detail: String(entry?.initiative_detail || ''),
                };
            }

            function saveDraft() {
                localStorage.setItem(storageKey, JSON.stringify(state));
            }

            function firstApiError(data, fallback) {
                if (data?.message) {
                    return data.message;
                }

                const firstGroup = Object.values(data?.errors || {})[0];

                if (Array.isArray(firstGroup) && firstGroup.length) {
                    return firstGroup[0];
                }

                return fallback;
            }

            // Developer context: This shared notice helper keeps success, info, and error feedback consistent across the DM page tools.
            // Clear explanation: This shows a message box on the DM page.
            function notice(target, message, type = 'info') {
                if (! target) {
                    return;
                }

                target.textContent = message;
                target.className = `notice show ${type}`;
            }

            // Developer context: This clears one notice area without touching the rest of the DM page state.
            // Clear explanation: This hides a page message after it is no longer needed.
            function clearNotice(target) {
                if (! target) {
                    return;
                }

                target.textContent = '';
                target.className = 'notice';
            }

            // Developer context: This mirrors the same "Draft restored" wording used on the other main pages.
            // Clear explanation: This tells the DM which parts of the page came back from the local draft.
            function showDraftRestoreNotice(parts) {
                if (! draftNotice || ! Array.isArray(parts) || parts.length === 0) {
                    return;
                }

                notice(draftNotice, `Draft restored: ${parts.join(', ')}.`, 'success');
                window.setTimeout(() => clearNotice(draftNotice), 5000);
            }

            function abilityModifier(score) {
                return Math.floor((Number(score) - 10) / 2);
            }

            function proficiencyBonus(level) {
                const parsedLevel = Number(level);
                return Number.isFinite(parsedLevel) && parsedLevel > 0 ? 2 + Math.floor((parsedLevel - 1) / 4) : 2;
            }

            function estimatedHitPoints(character) {
                const hitDie = Number(dmPageData.hit_die_by_class?.[character.class] || 0);
                const level = Number(character.level || 1);
                const conMod = abilityModifier(character.constitution || 10);
                const hpAdjustment = Number(character.hp_adjustment || 0);

                if (! hitDie || level < 1) {
                    return 1;
                }

                const firstLevel = hitDie + conMod;
                const extraLevels = Math.max(0, level - 1);
                const averageGain = Math.max(1, Math.floor(hitDie / 2) + 1 + conMod);

                return Math.max(1, firstLevel + (extraLevels * averageGain) + hpAdjustment);
            }

            function skillTotal(character, skill) {
                const abilityKey = dmPageData.skill_ability_map?.[skill];
                const base = abilityKey ? abilityModifier(character[abilityKey] || 10) : 0;
                const pb = proficiencyBonus(character.level || 1);
                const expertise = Array.isArray(character.skill_expertise) && character.skill_expertise.includes(skill);
                const proficient = Array.isArray(character.skill_proficiencies) && character.skill_proficiencies.includes(skill);

                return base + (expertise ? (pb * 2) : proficient ? pb : 0);
            }

            function signed(value) {
                const number = Number(value || 0);
                return `${number >= 0 ? '+' : ''}${number}`;
            }

            function commaList(value) {
                return String(value || '')
                    .split(',')
                    .map((item) => item.trim())
                    .filter(Boolean);
            }

            function parseMonsterHp(value) {
                const match = String(value || '').match(/(\d+)/);
                return match ? Number(match[1]) : 1;
            }

            function parseMonsterInitiativeBonus(value) {
                const match = String(value || '').match(/([+-]\d+)/);
                return match ? Number(match[1]) : 0;
            }

            function sortedCombatants() {
                return [...state.encounter.combatants].sort((a, b) => {
                    const initiativeA = Number.isFinite(a.initiative) ? a.initiative : -9999;
                    const initiativeB = Number.isFinite(b.initiative) ? b.initiative : -9999;

                    return initiativeB - initiativeA
                        || (b.initiative_bonus || 0) - (a.initiative_bonus || 0)
                        || (a.order || 0) - (b.order || 0);
                });
            }

            function updateGlance() {
                document.getElementById('glance-party-count').textContent = characters.length;
                document.getElementById('glance-monster-count').textContent = monsters.length;
                document.getElementById('glance-record-count').textContent = dmRecords.length;
                document.getElementById('glance-round').textContent = state.encounter.round;

                const activeCombatant = state.encounter.combatants.find((combatant) => combatant.id === state.encounter.activeId);
                document.getElementById('glance-active').textContent = activeCombatant ? activeCombatant.name : 'No one yet';

                document.getElementById('encounter-count').textContent = state.encounter.combatants.length;
                document.getElementById('encounter-enemies').textContent = state.encounter.combatants.filter((combatant) => combatant.side === 'enemy').length;
                document.getElementById('encounter-round').textContent = state.encounter.round;
                document.getElementById('encounter-active').textContent = activeCombatant ? activeCombatant.name : 'No one yet';
            }

            function renderWizardLog() {
                if (! state.wizard.log.length) {
                    wizardLog.innerHTML = '<div class="empty">The DM wizard is ready. Start with `new npc`, `new scene`, `new encounter`, or another DM command.</div>';
                    return;
                }

                wizardLog.innerHTML = state.wizard.log.map((entry) => `
                    <article class="wizard-message ${escapeHtml(entry.role || 'assistant')}">
                        <strong>${entry.role === 'user' ? 'You' : 'DM Wizard'}</strong>
                        <div>${escapeHtml(entry.message || '').replace(/\n/g, '<br>')}</div>
                    </article>
                `).join('');
            }

            function renderWizardQuickActions() {
                if (! state.wizard.quickActions.length) {
                    wizardQuickActions.innerHTML = '<div class="empty">Quick actions will show up here when the wizard has something useful to suggest.</div>';
                    return;
                }

                wizardQuickActions.innerHTML = state.wizard.quickActions.map((action) => `
                    <button class="chip-button" type="button" data-wizard-action="${escapeHtml(action)}">${escapeHtml(action)}</button>
                `).join('');
            }

            function renderWizardSnapshot() {
                const snapshot = state.wizard.snapshot;

                if (! snapshot) {
                    wizardSnapshot.innerHTML = '<div class="empty">Open a DM draft and the current snapshot will appear here.</div>';
                    return;
                }

                const fields = Array.isArray(snapshot.fields) ? snapshot.fields : [];
                const patch = snapshot.page_patch || {};
                const actions = [];

                if (patch.session) {
                    actions.push('<button class="mini-button good" type="button" data-snapshot-action="apply-session">Apply to session board</button>');
                }

                if (patch.encounter) {
                    actions.push('<button class="mini-button good" type="button" data-snapshot-action="apply-encounter">Apply to encounter tracker</button>');
                }

                if (patch.npc_combatant) {
                    actions.push('<button class="mini-button good" type="button" data-snapshot-action="apply-npc">Add NPC to encounter</button>');
                }

                wizardSnapshot.innerHTML = `
                    <div>
                        <h3>${escapeHtml(snapshot.title || 'Current draft')}</h3>
                        <p>${escapeHtml(snapshot.summary || 'No summary yet.')}</p>
                        ${snapshot.status ? `<p class="tiny">Status: ${escapeHtml(snapshot.status)}</p>` : ''}
                        ${snapshot.pending_field ? `<p class="tiny">Next step: ${escapeHtml(String(snapshot.pending_field).replaceAll('_', ' '))}</p>` : ''}
                    </div>
                    ${fields.length ? `
                        <div class="snapshot-fields">
                            ${fields.map((field) => `
                                <div class="snapshot-field">
                                    <strong>${escapeHtml(field.label || 'Field')}</strong>
                                    <span>${escapeHtml(field.value || '')}</span>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<p class="tiny">The snapshot will fill out as the draft grows.</p>'}
                    ${actions.length ? `<div class="snapshot-actions">${actions.join('')}</div>` : ''}
                `;
            }

            function renderDmRecords() {
                if (! dmRecords.length) {
                    dmRecordsList.innerHTML = '<div class="empty">No DM records are saved yet. Save a DM wizard draft to build a reusable record shelf.</div>';
                    return;
                }

                dmRecordsList.innerHTML = dmRecords.map((record) => `
                    <article class="entry">
                        <div class="entry-top">
                            <div>
                                <h3>${escapeHtml(record.name || 'Untitled')}</h3>
                                <p class="meta">${escapeHtml(record.kind || 'record')} · ${escapeHtml(record.status || 'draft')} · #${escapeHtml(record.id)}</p>
                            </div>
                            <div class="chip-list">
                                ${record.linked_homebrew_entry_id ? '<span class="chip good">Exported</span>' : ''}
                            </div>
                        </div>
                        <p>${escapeHtml(record.summary || 'No summary yet.')}</p>
                        <div class="combatant-actions">
                            <button class="mini-button" type="button" data-record-load="${escapeHtml(record.id)}">Load</button>
                            <button class="mini-button info" type="button" data-record-export="${escapeHtml(record.id)}">Export</button>
                            <button class="mini-button danger" type="button" data-record-delete="${escapeHtml(record.id)}">Delete</button>
                        </div>
                    </article>
                `).join('');
            }

            function renderWizard() {
                renderWizardLog();
                renderWizardQuickActions();
                renderWizardSnapshot();
                renderDmRecords();
            }

            async function sendWizardMessage(message) {
                const trimmed = String(message || '').trim();

                if (! trimmed && ! state.wizard.state?.pending_field) {
                    return;
                }

                if (trimmed) {
                    state.wizard.log.push({ role: 'user', message: trimmed });
                    state.wizard.log = state.wizard.log.slice(-30);
                }

                state.wizard.input = '';
                saveDraft();
                renderWizard();

                try {
                    const response = await fetch(endpoints.dmWizard, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({
                            message: trimmed,
                            state: state.wizard.state || {},
                        }),
                    });

                    const data = await response.json();

                    if (! response.ok) {
                        throw new Error(firstApiError(data, 'The DM wizard could not process that message.'));
                    }

                    state.wizard.state = data.state || {};
                    state.wizard.quickActions = Array.isArray(data.quick_actions) ? data.quick_actions.slice(0, 12) : [];
                    state.wizard.snapshot = data.snapshot && typeof data.snapshot === 'object' ? data.snapshot : null;
                    state.wizard.log.push({ role: 'assistant', message: data.reply || 'No reply returned.' });
                    state.wizard.log = state.wizard.log.slice(-30);
                    saveDraft();
                    renderWizard();
                    clearNotice(wizardNotice);

                    if (/save record|export to homebrew/i.test(trimmed)) {
                        await loadRemoteData();
                    }
                } catch (error) {
                    notice(wizardNotice, error.message, 'error');
                }
            }

            function applySessionPatch(sessionPatch) {
                if (! sessionPatch || typeof sessionPatch !== 'object') {
                    return;
                }

                state.session = {
                    ...state.session,
                    ...sessionPatch,
                };
                saveDraft();
                syncSessionForm();
                notice(draftNotice, 'Wizard draft applied to the session board.', 'success');
            }

            function applyEncounterPatch(encounterPatch) {
                if (! encounterPatch || typeof encounterPatch !== 'object') {
                    return;
                }

                state.encounter = {
                    round: Number(encounterPatch.round || 1),
                    activeId: encounterPatch.activeId || null,
                    combatants: Array.isArray(encounterPatch.combatants)
                        ? encounterPatch.combatants.map(normalizeCombatant)
                        : [],
                };
                saveDraft();
                renderEncounter();
                notice(encounterNotice, 'Wizard draft applied to the encounter tracker.', 'success');
            }

            function syncSessionForm() {
                for (const [key, value] of Object.entries(state.session)) {
                    const element = document.getElementById(`session-${key.replaceAll('_', '-')}`);
                    if (element) {
                        element.value = value ?? '';
                    }
                }
            }

            function renderEncounter() {
                const orderedCombatants = sortedCombatants();

                if (! orderedCombatants.length) {
                    combatantList.innerHTML = '<div class="empty">No one is in the encounter yet. Add a party member, a monster from the vault, or a custom combatant to start tracking the scene.</div>';
                    updateGlance();
                    return;
                }

                combatantList.innerHTML = orderedCombatants.map((combatant, index) => {
                    const conditions = combatant.conditions.length
                        ? `<div class="chip-list">${combatant.conditions.map((condition) => `<span class="chip">${escapeHtml(condition)}</span>`).join('')}</div>`
                        : '<p class="tiny">No active conditions.</p>';

                    const sideClass = combatant.side === 'enemy'
                        ? 'enemy'
                        : (combatant.side === 'party' || combatant.side === 'ally' ? 'ally' : '');

                    return `
                        <article class="combatant ${combatant.id === state.encounter.activeId ? 'active' : ''}" data-combatant-id="${escapeHtml(combatant.id)}">
                            <div class="combatant-top">
                                <div>
                                    <h3>${index + 1}. ${escapeHtml(combatant.name)}</h3>
                                    <p class="meta">${escapeHtml(combatant.sourceLabel)} · ${escapeHtml(combatant.side)} · Initiative ${combatant.initiative ?? 'not rolled'} ${combatant.initiative_bonus ? `(${signed(combatant.initiative_bonus)})` : ''}</p>
                                </div>
                                <div class="chip-list">
                                    <span class="chip ${sideClass}">${escapeHtml(combatant.side)}</span>
                                    ${combatant.id === state.encounter.activeId ? '<span class="chip good">Active turn</span>' : ''}
                                </div>
                            </div>
                            <div class="combatant-grid">
                                <label>Initiative<input type="number" data-field="initiative" value="${combatant.initiative ?? ''}"></label>
                                <label>Init bonus<input type="number" data-field="initiative_bonus" value="${combatant.initiative_bonus ?? 0}"></label>
                                <label>AC<input type="text" data-field="ac" value="${escapeHtml(combatant.ac)}"></label>
                                <label>Current HP<input type="number" min="0" data-field="current_hp" value="${combatant.current_hp}"></label>
                                <label>Max HP<input type="number" min="0" data-field="max_hp" value="${combatant.max_hp}"></label>
                                <label>Temp HP<input type="number" min="0" data-field="temp_hp" value="${combatant.temp_hp}"></label>
                                <label class="full">Conditions<input type="text" data-field="conditions_text" list="condition-options" value="${escapeHtml(combatant.conditions.join(', '))}"></label>
                                <label class="full">Notes<input type="text" data-field="note" maxlength="240" value="${escapeHtml(combatant.note)}"></label>
                            </div>
                            ${conditions}
                            ${combatant.initiative_detail ? `<p class="tiny">Last initiative roll: ${escapeHtml(combatant.initiative_detail)}</p>` : ''}
                            <div class="inline-grid">
                                <label class="full">Quick adjustment<input type="number" value="0" data-field="adjustment"></label>
                            </div>
                            <div class="combatant-actions">
                                <button class="mini-button danger" type="button" data-action="damage">Damage</button>
                                <button class="mini-button good" type="button" data-action="heal">Heal</button>
                                <button class="mini-button info" type="button" data-action="temp">Temp HP</button>
                                <button class="mini-button" type="button" data-action="roll-init">Roll init</button>
                                <button class="mini-button" type="button" data-action="set-active">Set active</button>
                                <button class="mini-button danger" type="button" data-action="remove">Remove</button>
                            </div>
                        </article>
                    `;
                }).join('');

                updateGlance();
            }

            function renderDiceLog() {
                if (! state.dice.log.length) {
                    diceLog.innerHTML = '<div class="empty">Rolls will appear here with the expression, total, and detail string.</div>';
                    return;
                }

                diceLog.innerHTML = state.dice.log.map((entry) => `
                    <article class="log-entry">
                        <strong>${escapeHtml(entry.context || 'Unnamed roll')}</strong>
                        <p>${escapeHtml(entry.expression)}${entry.mode ? ` (${escapeHtml(entry.mode)})` : ''} = <strong>${escapeHtml(entry.total)}</strong></p>
                        <p class="tiny">${escapeHtml(entry.detail)}</p>
                    </article>
                `).join('');
            }

            function renderParty() {
                const search = state.filters.partySearch.toLowerCase();
                const sorted = [...characters].sort((a, b) => {
                    if (state.filters.partySort === 'level') {
                        return Number(b.level || 0) - Number(a.level || 0) || String(a.name).localeCompare(String(b.name));
                    }

                    if (state.filters.partySort === 'class') {
                        return String(a.class || '').localeCompare(String(b.class || '')) || String(a.name).localeCompare(String(b.name));
                    }

                    return String(a.name || '').localeCompare(String(b.name || ''));
                });

                const filtered = sorted.filter((character) => {
                    const haystack = [
                        character.name,
                        character.species,
                        character.class,
                        character.subclass,
                        character.background,
                        character.alignment,
                        character.goals,
                        character.notes,
                        ...(character.languages || []),
                        ...(character.skill_proficiencies || []),
                    ].join(' ').toLowerCase();

                    return haystack.includes(search);
                });

                if (! filtered.length) {
                    partyCards.innerHTML = '<div class="empty">No saved characters match this search yet.</div>';
                    return;
                }

                partyCards.innerHTML = filtered.map((character) => {
                    const hp = estimatedHitPoints(character);
                    const passivePerception = 10 + skillTotal(character, 'Perception');
                    const passiveInsight = 10 + skillTotal(character, 'Insight');
                    const passiveInvestigation = 10 + skillTotal(character, 'Investigation');

                    return `
                        <article class="entry">
                            <div class="entry-top">
                                <div>
                                    <h3>${escapeHtml(character.name)}</h3>
                                    <p class="meta">Level ${escapeHtml(character.level)} ${escapeHtml(character.species)} ${escapeHtml(character.class)}${character.subclass ? ` · ${escapeHtml(character.subclass)}` : ''}</p>
                                </div>
                                <div class="chip-list">
                                    <span class="chip">${escapeHtml(character.background)}</span>
                                    ${character.alignment ? `<span class="chip">${escapeHtml(character.alignment)}</span>` : ''}
                                </div>
                            </div>
                            <p class="tiny">Estimated HP ${hp} · Initiative ${signed(abilityModifier(character.dexterity || 10))} · Passive Perception ${passivePerception} · Insight ${passiveInsight} · Investigation ${passiveInvestigation}</p>
                            <div class="chip-list">
                                ${(character.languages || []).slice(0, 4).map((language) => `<span class="chip">${escapeHtml(language)}</span>`).join('')}
                                ${(character.skill_proficiencies || []).slice(0, 4).map((skill) => `<span class="chip">${escapeHtml(skill)}</span>`).join('')}
                            </div>
                            ${character.goals ? `<p>${escapeHtml(character.goals)}</p>` : '<p class="tiny">No goal saved on the sheet.</p>'}
                            <div class="actions">
                                <button class="btn-soft" type="button" data-character-add="${escapeHtml(character.id)}">Add to encounter</button>
                            </div>
                        </article>
                    `;
                }).join('');
            }

            function renderMonsters() {
                const search = state.filters.monsterSearch.toLowerCase();
                const cr = state.filters.monsterCr;
                const filtered = monsters.filter((monster) => {
                    const haystack = [
                        monster.name,
                        monster.summary,
                        monster.creature_type,
                        monster.alignment,
                        monster.languages,
                        monster.skills,
                        ...(monster.trait_names || []),
                        ...(monster.action_names || []),
                    ].join(' ').toLowerCase();

                    return haystack.includes(search) && (! cr || String(monster.cr || '') === cr);
                }).slice(0, 24);

                if (! filtered.length) {
                    monsterCards.innerHTML = '<div class="empty">No monsters match this filter right now.</div>';
                    return;
                }

                monsterCards.innerHTML = filtered.map((monster) => `
                    <article class="entry">
                        <div class="entry-top">
                            <div>
                                <h3>${escapeHtml(monster.name)}</h3>
                                <p class="meta">CR ${escapeHtml(monster.cr || '—')} · AC ${escapeHtml(monster.ac || '—')} · HP ${escapeHtml(monster.hp || '—')}</p>
                            </div>
                            <div class="chip-list">
                                <span class="chip">${escapeHtml(monster.creature_type || 'Creature')}</span>
                                ${monster.legendary_action_names?.length ? '<span class="chip enemy">Legendary</span>' : ''}
                            </div>
                        </div>
                        <p>${escapeHtml(monster.summary || 'No summary available.')}</p>
                        <div class="chip-list">
                            <span class="chip">Init ${escapeHtml(monster.initiative || '—')}</span>
                            <span class="chip">${escapeHtml(monster.speed || 'No speed listed')}</span>
                            ${monster.senses ? `<span class="chip">${escapeHtml(monster.senses)}</span>` : ''}
                        </div>
                        <p class="tiny">${escapeHtml(monster.languages || 'No languages listed.')}</p>
                        <p class="tiny">Traits ${monster.trait_names?.length || 0} · Actions ${monster.action_names?.length || 0} · Reactions ${monster.reaction_names?.length || 0}</p>
                        <div class="actions">
                            <button class="btn-soft" type="button" data-monster-add="${escapeHtml(monster.name)}">Add to encounter</button>
                        </div>
                    </article>
                `).join('');
            }

            function renderHomebrew() {
                const search = state.filters.homebrewSearch.toLowerCase();
                const statusFilter = state.filters.homebrewStatus;
                const allowedStatuses = statusFilter
                    ? [statusFilter]
                    : ['table-ready', 'playtest'];

                const filtered = homebrewEntries.filter((entry) => {
                    const haystack = [
                        entry.name,
                        entry.summary,
                        entry.details,
                        entry.source_notes,
                        ...(entry.tags || []),
                    ].join(' ').toLowerCase();

                    return allowedStatuses.includes(entry.status) && haystack.includes(search);
                });

                if (! filtered.length) {
                    homebrewCards.innerHTML = '<div class="empty">No workshop entries match this filter yet.</div>';
                    return;
                }

                homebrewCards.innerHTML = filtered.map((entry) => `
                    <article class="entry">
                        <div class="entry-top">
                            <div>
                                <h3>${escapeHtml(entry.name)}</h3>
                                <p class="meta">${escapeHtml(entry.category)} · ${escapeHtml(entry.status)}</p>
                            </div>
                            <div class="chip-list">
                                <span class="chip ${entry.status === 'table-ready' ? 'good' : ''}">${escapeHtml(entry.status)}</span>
                            </div>
                        </div>
                        <p>${escapeHtml(entry.summary)}</p>
                        ${entry.details ? `<p class="tiny">${escapeHtml(entry.details)}</p>` : ''}
                        <div class="chip-list">
                            ${(entry.tags || []).map((tag) => `<span class="chip">${escapeHtml(tag)}</span>`).join('')}
                        </div>
                    </article>
                `).join('');
            }

            function populateCharacterSelect() {
                encounterCharacterSelect.innerHTML = [
                    '<option value="">Choose a saved character</option>',
                    ...characters.map((character) => `<option value="${escapeHtml(character.id)}">${escapeHtml(character.name)} · Level ${escapeHtml(character.level)} ${escapeHtml(character.class)}</option>`),
                ].join('');
            }

            function populateMonsterCrFilter() {
                const values = [...new Set(monsters.map((monster) => String(monster.cr || '')).filter(Boolean))].sort((a, b) => Number(a) - Number(b) || a.localeCompare(b));
                monsterCrFilter.innerHTML = [
                    '<option value="">All challenge ratings</option>',
                    ...values.map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`),
                ].join('');
                monsterCrFilter.value = state.filters.monsterCr;
            }

            function characterToCombatant(character) {
                return normalizeCombatant({
                    id: crypto.randomUUID(),
                    order: Date.now(),
                    source: 'character',
                    sourceLabel: 'Saved character',
                    name: character.name,
                    side: 'party',
                    initiative: null,
                    initiative_bonus: abilityModifier(character.dexterity || 10),
                    ac: '',
                    current_hp: estimatedHitPoints(character),
                    max_hp: estimatedHitPoints(character),
                    temp_hp: 0,
                    conditions: [],
                    note: `${character.background || 'Background not set'}${character.alignment ? ` · ${character.alignment}` : ''}`,
                });
            }

            function monsterToCombatant(monster) {
                const existingCopies = state.encounter.combatants.filter((combatant) => combatant.name.startsWith(monster.name)).length;
                const duplicateSuffix = existingCopies ? ` ${existingCopies + 1}` : '';

                return normalizeCombatant({
                    id: crypto.randomUUID(),
                    order: Date.now(),
                    source: 'monster',
                    sourceLabel: 'Monster vault',
                    name: `${monster.name}${duplicateSuffix}`,
                    side: 'enemy',
                    initiative: null,
                    initiative_bonus: parseMonsterInitiativeBonus(monster.initiative),
                    ac: monster.ac || '',
                    current_hp: parseMonsterHp(monster.hp),
                    max_hp: parseMonsterHp(monster.hp),
                    temp_hp: 0,
                    conditions: [],
                    note: `CR ${monster.cr || '—'} · ${monster.creature_type || 'Creature'}`,
                });
            }

            function addCombatant(combatant) {
                state.encounter.combatants.push(normalizeCombatant(combatant));
                saveDraft();
                renderEncounter();
                notice(encounterNotice, `${combatant.name} added to the encounter.`, 'success');
            }

            function adjustCombatantHitPoints(combatant, action, amount) {
                if (! Number.isFinite(amount) || amount < 0) {
                    return;
                }

                if (action === 'damage') {
                    const tempSpent = Math.min(combatant.temp_hp, amount);
                    const remainingDamage = amount - tempSpent;
                    combatant.temp_hp = Math.max(0, combatant.temp_hp - tempSpent);
                    combatant.current_hp = Math.max(0, combatant.current_hp - remainingDamage);
                    return;
                }

                if (action === 'heal') {
                    combatant.current_hp = Math.min(combatant.max_hp || Number.MAX_SAFE_INTEGER, combatant.current_hp + amount);
                    return;
                }

                if (action === 'temp') {
                    combatant.temp_hp = Math.max(combatant.temp_hp, amount);
                }
            }

            function advanceTurn() {
                const orderedCombatants = sortedCombatants();

                if (! orderedCombatants.length) {
                    notice(encounterNotice, 'Add at least one combatant before advancing the turn order.', 'error');
                    return;
                }

                const currentIndex = orderedCombatants.findIndex((combatant) => combatant.id === state.encounter.activeId);

                if (currentIndex === -1) {
                    state.encounter.activeId = orderedCombatants[0].id;
                } else {
                    const nextIndex = (currentIndex + 1) % orderedCombatants.length;
                    if (nextIndex === 0) {
                        state.encounter.round += 1;
                    }
                    state.encounter.activeId = orderedCombatants[nextIndex].id;
                }

                saveDraft();
                renderEncounter();
            }

            async function rollDiceApi(expression, mode = '') {
                const response = await fetch(endpoints.rollDice, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({
                        expression,
                        mode: mode || null,
                    }),
                });

                const data = await response.json();

                if (! response.ok) {
                    throw new Error(data.message || 'The roll could not be completed.');
                }

                return data;
            }

            async function rollCombatantInitiative(combatant) {
                const expression = combatant.initiative_bonus
                    ? `1d20${combatant.initiative_bonus >= 0 ? '+' : ''}${combatant.initiative_bonus}`
                    : '1d20';
                const result = await rollDiceApi(expression);

                combatant.initiative = result.total;
                combatant.initiative_detail = result.detail;
            }

            async function loadRemoteData() {
                const [characterResponse, monsterResponse, homebrewResponse, dmRecordResponse] = await Promise.all([
                    fetch(endpoints.characters, { headers: { Accept: 'application/json' } }),
                    fetch(endpoints.monsters, { headers: { Accept: 'application/json' } }),
                    fetch(endpoints.homebrew, { headers: { Accept: 'application/json' } }),
                    fetch(endpoints.dmRecords, { headers: { Accept: 'application/json' } }),
                ]);

                if (! characterResponse.ok || ! monsterResponse.ok || ! homebrewResponse.ok || ! dmRecordResponse.ok) {
                    throw new Error('The DM desk could not load one of its shared data sources.');
                }

                characters = await characterResponse.json();
                monsters = (await monsterResponse.json()).section?.items || [];
                homebrewEntries = (await homebrewResponse.json()).entries || [];
                dmRecords = (await dmRecordResponse.json()).records || [];

                populateCharacterSelect();
                populateMonsterCrFilter();
                renderParty();
                renderMonsters();
                renderHomebrew();
                renderDmRecords();
                renderEncounter();
                renderWizard();
                updateGlance();
            }

            function syncControls() {
                partySearch.value = state.filters.partySearch;
                partySort.value = state.filters.partySort;
                monsterSearch.value = state.filters.monsterSearch;
                homebrewSearch.value = state.filters.homebrewSearch;
                homebrewStatusFilter.value = state.filters.homebrewStatus;
                diceExpression.value = state.dice.expression;
                diceMode.value = state.dice.mode;
                diceContext.value = state.dice.context;
                wizardInput.value = state.wizard.input;
                syncSessionForm();
                renderWizard();
            }

            wizardInput.addEventListener('input', () => {
                state.wizard.input = wizardInput.value;
                saveDraft();
            });

            wizardForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                await sendWizardMessage(wizardInput.value);
                wizardInput.value = '';
            });

            wizardQuickActions.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-wizard-action]');
                if (! button) {
                    return;
                }

                await sendWizardMessage(button.dataset.wizardAction);
            });

            wizardSnapshot.addEventListener('click', (event) => {
                const button = event.target.closest('[data-snapshot-action]');
                if (! button) {
                    return;
                }

                const patch = state.wizard.snapshot?.page_patch || {};

                if (button.dataset.snapshotAction === 'apply-session') {
                    applySessionPatch(patch.session);
                    return;
                }

                if (button.dataset.snapshotAction === 'apply-encounter') {
                    applyEncounterPatch(patch.encounter);
                    return;
                }

                if (button.dataset.snapshotAction === 'apply-npc' && patch.npc_combatant) {
                    addCombatant(patch.npc_combatant);
                }
            });

            dmRecordsList.addEventListener('click', async (event) => {
                const loadButton = event.target.closest('[data-record-load]');
                if (loadButton) {
                    await sendWizardMessage(`load dm record ${loadButton.dataset.recordLoad}`);
                    return;
                }

                const exportButton = event.target.closest('[data-record-export]');
                if (exportButton) {
                    try {
                        const response = await fetch(`${endpoints.dmRecords}/${exportButton.dataset.recordExport}/export-homebrew`, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                            },
                        });
                        const data = await response.json();

                        if (! response.ok) {
                            throw new Error(firstApiError(data, 'The DM record could not be exported.'));
                        }

                        await loadRemoteData();
                        notice(wizardNotice, data.message || 'DM record exported to homebrew.', 'success');
                    } catch (error) {
                        notice(wizardNotice, error.message, 'error');
                    }

                    return;
                }

                const deleteButton = event.target.closest('[data-record-delete]');
                if (! deleteButton) {
                    return;
                }

                try {
                    const response = await fetch(`${endpoints.dmRecords}/${deleteButton.dataset.recordDelete}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                        },
                    });
                    const data = await response.json();

                    if (! response.ok) {
                        throw new Error(firstApiError(data, 'The DM record could not be removed.'));
                    }

                    if (String(state.wizard.state?.page_linkage?.last_saved_record_id || '') === deleteButton.dataset.recordDelete) {
                        state.wizard.state.page_linkage.last_saved_record_id = null;
                    }

                    if (String(state.wizard.state?.draft_record?.id || '') === deleteButton.dataset.recordDelete) {
                        state.wizard.state.draft_record.id = null;
                    }

                    await loadRemoteData();
                    saveDraft();
                    notice(wizardNotice, data.message || 'DM record removed.', 'success');
                } catch (error) {
                    notice(wizardNotice, error.message, 'error');
                }
            });

            sessionForm.addEventListener('input', (event) => {
                const target = event.target;
                if (! target.id.startsWith('session-')) {
                    return;
                }

                const key = target.id.replace('session-', '').replaceAll('-', '_');
                state.session[key] = target.value;
                saveDraft();
            });

            document.getElementById('reset-session').addEventListener('click', () => {
                state.session = structuredClone(defaultState.session);
                saveDraft();
                syncSessionForm();
                notice(draftNotice, 'Session notes cleared from the local draft.', 'success');
            });

            document.getElementById('clear-drafts').addEventListener('click', () => {
                localStorage.removeItem(storageKey);
                state = structuredClone(defaultState);
                syncControls();
                renderEncounter();
                renderDiceLog();
                renderParty();
                renderMonsters();
                renderHomebrew();
                renderWizard();
                clearNotice(encounterNotice);
                clearNotice(wizardNotice);
                notice(draftNotice, 'Local DM draft cleared for this browser.', 'success');
            });

            document.getElementById('add-character-to-encounter').addEventListener('click', () => {
                const selected = characters.find((character) => String(character.id) === encounterCharacterSelect.value);

                if (! selected) {
                    notice(encounterNotice, 'Pick a saved character before adding a party member to the encounter.', 'error');
                    return;
                }

                addCombatant(characterToCombatant(selected));
            });

            customCombatantForm.addEventListener('submit', (event) => {
                event.preventDefault();

                const combatant = {
                    id: crypto.randomUUID(),
                    order: Date.now(),
                    source: 'custom',
                    sourceLabel: 'Custom entry',
                    name: document.getElementById('custom-name').value.trim(),
                    side: document.getElementById('custom-side').value,
                    initiative: null,
                    initiative_bonus: Number(document.getElementById('custom-init-bonus').value || 0),
                    ac: document.getElementById('custom-ac').value.trim(),
                    current_hp: Number(document.getElementById('custom-current-hp').value || 0),
                    max_hp: Number(document.getElementById('custom-max-hp').value || 0),
                    temp_hp: Number(document.getElementById('custom-temp-hp').value || 0),
                    conditions: commaList(document.getElementById('custom-conditions').value),
                    note: document.getElementById('custom-note').value.trim(),
                };

                if (! combatant.name) {
                    notice(encounterNotice, 'Give the custom combatant a name before saving it to the tracker.', 'error');
                    return;
                }

                addCombatant(combatant);
                customCombatantForm.reset();
                document.getElementById('custom-current-hp').value = 1;
                document.getElementById('custom-max-hp').value = 1;
                document.getElementById('custom-temp-hp').value = 0;
                document.getElementById('custom-init-bonus').value = 0;
            });

            document.getElementById('next-turn').addEventListener('click', () => {
                advanceTurn();
            });

            document.getElementById('clear-encounter').addEventListener('click', () => {
                state.encounter = structuredClone(defaultState.encounter);
                saveDraft();
                renderEncounter();
                notice(encounterNotice, 'Encounter tracker cleared from the local draft.', 'success');
            });

            document.getElementById('roll-missing-init').addEventListener('click', async () => {
                const missing = state.encounter.combatants.filter((combatant) => ! Number.isFinite(combatant.initiative));

                if (! missing.length) {
                    notice(encounterNotice, 'Everyone in the encounter already has an initiative result.', 'info');
                    return;
                }

                try {
                    for (const combatant of missing) {
                        await rollCombatantInitiative(combatant);
                    }

                    saveDraft();
                    renderEncounter();
                    notice(encounterNotice, 'Rolled initiative for every combatant that was still missing it.', 'success');
                } catch (error) {
                    notice(encounterNotice, error.message, 'error');
                }
            });

            combatantList.addEventListener('input', (event) => {
                const card = event.target.closest('[data-combatant-id]');
                if (! card) {
                    return;
                }

                const combatant = state.encounter.combatants.find((entry) => entry.id === card.dataset.combatantId);
                if (! combatant) {
                    return;
                }

                const field = event.target.dataset.field;
                if (! field) {
                    return;
                }

                if (field === 'conditions_text') {
                    combatant.conditions = commaList(event.target.value);
                } else if (['initiative', 'initiative_bonus', 'current_hp', 'max_hp', 'temp_hp'].includes(field)) {
                    combatant[field] = event.target.value === '' ? null : Number(event.target.value);
                } else {
                    combatant[field] = event.target.value;
                }

                saveDraft();
                renderEncounter();
            });

            combatantList.addEventListener('click', async (event) => {
                const actionTarget = event.target.closest('[data-action]');
                if (! actionTarget) {
                    return;
                }

                const card = actionTarget.closest('[data-combatant-id]');
                if (! card) {
                    return;
                }

                const combatant = state.encounter.combatants.find((entry) => entry.id === card.dataset.combatantId);
                if (! combatant) {
                    return;
                }

                const adjustmentInput = card.querySelector('[data-field="adjustment"]');
                const adjustmentValue = Number(adjustmentInput?.value || 0);

                if (actionTarget.dataset.action === 'remove') {
                    state.encounter.combatants = state.encounter.combatants.filter((entry) => entry.id !== combatant.id);
                    if (state.encounter.activeId === combatant.id) {
                        state.encounter.activeId = null;
                    }
                    saveDraft();
                    renderEncounter();
                    return;
                }

                if (actionTarget.dataset.action === 'set-active') {
                    state.encounter.activeId = combatant.id;
                    saveDraft();
                    renderEncounter();
                    return;
                }

                if (actionTarget.dataset.action === 'roll-init') {
                    try {
                        await rollCombatantInitiative(combatant);
                        saveDraft();
                        renderEncounter();
                        notice(encounterNotice, `Rolled initiative for ${combatant.name}.`, 'success');
                    } catch (error) {
                        notice(encounterNotice, error.message, 'error');
                    }
                    return;
                }

                if (! Number.isFinite(adjustmentValue) || adjustmentValue < 0) {
                    notice(encounterNotice, 'Use a zero-or-higher quick adjustment value before changing HP.', 'error');
                    return;
                }

                adjustCombatantHitPoints(combatant, actionTarget.dataset.action, adjustmentValue);
                saveDraft();
                renderEncounter();
            });

            document.getElementById('dice-form').addEventListener('submit', async (event) => {
                event.preventDefault();

                try {
                    const result = await rollDiceApi(diceExpression.value, diceMode.value);
                    state.dice.expression = diceExpression.value;
                    state.dice.mode = diceMode.value;
                    state.dice.context = diceContext.value;
                    state.dice.log.unshift({
                        context: diceContext.value.trim() || 'Custom roll',
                        expression: result.expression,
                        mode: result.mode || '',
                        total: result.total,
                        detail: result.detail,
                    });
                    state.dice.log = state.dice.log.slice(0, 20);
                    saveDraft();
                    renderDiceLog();
                    notice(diceNotice, `Rolled ${result.expression} for ${diceContext.value.trim() || 'the dice tray'}.`, 'success');
                } catch (error) {
                    notice(diceNotice, error.message, 'error');
                }
            });

            document.querySelectorAll('[data-dice-expression]').forEach((button) => {
                button.addEventListener('click', async () => {
                    diceExpression.value = button.dataset.diceExpression || '';
                    diceMode.value = button.dataset.diceMode || '';

                    try {
                        const result = await rollDiceApi(diceExpression.value, diceMode.value);
                        state.dice.expression = diceExpression.value;
                        state.dice.mode = diceMode.value;
                        state.dice.log.unshift({
                            context: diceContext.value.trim() || 'Quick roll',
                            expression: result.expression,
                            mode: result.mode || '',
                            total: result.total,
                            detail: result.detail,
                        });
                        state.dice.log = state.dice.log.slice(0, 20);
                        saveDraft();
                        renderDiceLog();
                        notice(diceNotice, `Rolled ${result.expression}.`, 'success');
                    } catch (error) {
                        notice(diceNotice, error.message, 'error');
                    }
                });
            });

            partySearch.addEventListener('input', () => {
                state.filters.partySearch = partySearch.value;
                saveDraft();
                renderParty();
            });

            partySort.addEventListener('change', () => {
                state.filters.partySort = partySort.value;
                saveDraft();
                renderParty();
            });

            monsterSearch.addEventListener('input', () => {
                state.filters.monsterSearch = monsterSearch.value;
                saveDraft();
                renderMonsters();
            });

            monsterCrFilter.addEventListener('change', () => {
                state.filters.monsterCr = monsterCrFilter.value;
                saveDraft();
                renderMonsters();
            });

            homebrewSearch.addEventListener('input', () => {
                state.filters.homebrewSearch = homebrewSearch.value;
                saveDraft();
                renderHomebrew();
            });

            homebrewStatusFilter.addEventListener('change', () => {
                state.filters.homebrewStatus = homebrewStatusFilter.value;
                saveDraft();
                renderHomebrew();
            });

            document.getElementById('refresh-party').addEventListener('click', async () => {
                try {
                    await loadRemoteData();
                    notice(draftNotice, 'Shared party data refreshed.', 'success');
                } catch (error) {
                    notice(draftNotice, error.message, 'error');
                }
            });

            document.getElementById('refresh-monsters').addEventListener('click', async () => {
                try {
                    await loadRemoteData();
                    notice(draftNotice, 'Monster vault refreshed.', 'success');
                } catch (error) {
                    notice(draftNotice, error.message, 'error');
                }
            });

            partyCards.addEventListener('click', (event) => {
                const button = event.target.closest('[data-character-add]');
                if (! button) {
                    return;
                }

                const character = characters.find((entry) => String(entry.id) === button.dataset.characterAdd);
                if (! character) {
                    return;
                }

                addCombatant(characterToCombatant(character));
            });

            monsterCards.addEventListener('click', (event) => {
                const button = event.target.closest('[data-monster-add]');
                if (! button) {
                    return;
                }

                const monster = monsters.find((entry) => entry.name === button.dataset.monsterAdd);
                if (! monster) {
                    return;
                }

                addCombatant(monsterToCombatant(monster));
            });

            syncControls();
            renderEncounter();
            renderDiceLog();
            showDraftRestoreNotice(restoredDraftSections);

            loadRemoteData().catch((error) => {
                notice(draftNotice, error.message, 'error');
                partyCards.innerHTML = '<div class="empty">The DM desk could not load the saved characters right now.</div>';
                monsterCards.innerHTML = '<div class="empty">The DM desk could not load the monster compendium right now.</div>';
                homebrewCards.innerHTML = '<div class="empty">The DM desk could not load the homebrew entries right now.</div>';
            });
        </script>
    </body>
</html>
