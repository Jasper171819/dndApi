<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Adventurer's Ledger</title>
        <style>
            :root{--bg:#130f0d;--panel:#221915;--soft:#31231d;--line:#4e372a;--text:#f7ead8;--muted:#d8bea0;--accent:#d58345;--accent2:#efba70}
            *{box-sizing:border-box} html{scroll-behavior:smooth}
            body{position:relative;min-height:100vh;margin:0;font-family:"Trebuchet MS","Segoe UI",sans-serif;color:var(--text);background:#100c0b;overflow-x:hidden}
            body::before{content:"";position:fixed;inset:0;z-index:-2;background:radial-gradient(circle at top left,rgba(213,131,69,.22),transparent 25%),linear-gradient(160deg,#100c0b,#1b1411 50%,#0e0b0a)}
            body::after{content:"";position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(0,0,0,.06),rgba(0,0,0,.18))}
            button,input,textarea,select{font:inherit} a{text-decoration:none;color:inherit}
            .wrap{position:relative;z-index:1;width:min(1180px,calc(100% - 2rem));margin:0 auto;padding:1rem 0 4rem}
            .topbar,.card,.panel,.char,.stat,.notice,.mini{border:1px solid var(--line);border-radius:24px;background:rgba(34,25,21,.9);box-shadow:0 20px 50px rgba(0,0,0,.28)}
            .topbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem 1.2rem;margin-top:1rem;border-radius:999px;background:rgba(19,15,13,.84);backdrop-filter:blur(10px)}
            .brand{display:flex;align-items:center;gap:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
            .mark{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#25170f;font-weight:900}
            .nav{display:flex;flex-wrap:wrap;gap:.6rem}
            .nav a,.btn,.btn-soft{padding:.8rem 1rem;border-radius:999px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);cursor:pointer;transition:.18s ease}
            .btn:hover,.btn-soft:hover,.nav a:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
            .btn{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#29180f;font-weight:700}
            main#top{display:grid;gap:1.4rem}
            main#top > section{margin-top:0}
            .hero{display:grid;grid-template-columns:1fr;align-items:start;gap:1rem;padding:2rem 0 .4rem;order:1}
            .card,.panel{padding:1.5rem}
            .eyebrow{display:inline-block;margin-bottom:.8rem;color:var(--accent2);font-size:.78rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
            h1,h2,h3{margin:0;font-family:Georgia,"Times New Roman",serif;line-height:1.05}
            h1{font-size:clamp(2.7rem,5vw,4.6rem);max-width:9ch}
            h2{font-size:clamp(1.7rem,3vw,2.2rem)}
            p{color:var(--muted);line-height:1.7}
            .hero-actions,.actions,.quick{display:flex;flex-wrap:wrap;gap:.75rem}
            .quick{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));margin-top:1rem}
            .mini{padding:1rem;border-radius:18px;background:var(--soft)}
            .mini strong{display:block;margin-top:.35rem;font-size:1.15rem;color:var(--text)}
            section{margin-top:1.4rem}
            .head{display:flex;justify-content:space-between;align-items:end;gap:1rem;margin-bottom:1rem}
            .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}
            .char{padding:1rem;display:grid;gap:.9rem;background:rgba(255,255,255,.03)}
            .char-top{display:flex;justify-content:space-between;gap:1rem;align-items:start}
            .meta{color:var(--muted);font-size:.95rem}
            .stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem}
            .stat{padding:.75rem;border-radius:16px;text-align:center;background:rgba(255,255,255,.03)}
            .stat span{display:block}.stat .label{font-size:.72rem;letter-spacing:.08em;color:var(--accent2)} .stat .value{margin-top:.2rem;font-weight:700}
            .form{display:grid;gap:1rem}
            .form-section{padding:1rem;border-radius:22px;border:1px solid var(--line);background:rgba(255,255,255,.03)}
            .form-section + .form-section{margin-top:.1rem}
            .section-head{display:grid;gap:.35rem;margin-bottom:1rem}
            .section-kicker{font-size:.76rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--accent2)}
            .section-head p{margin:0}
            .section-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.9rem}
            .full{grid-column:1 / -1}
            label{display:grid;gap:.35rem;color:var(--muted);font-size:.92rem}
            input,textarea,select{width:100%;padding:.85rem 1rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--text);outline:none}
            textarea{min-height:7rem;resize:vertical}
            input:focus,textarea:focus,select:focus{border-color:#9e754f}
            .check-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}
            .check-chip{display:block;color:inherit;font-size:inherit}
            .check-chip-input{position:absolute;opacity:0;pointer-events:none}
            .check-chip-label{display:block;padding:.9rem 1rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--text);cursor:pointer;transition:.18s ease}
            .check-chip:hover .check-chip-label{border-color:#8b623f;transform:translateY(-1px)}
            .check-chip-input:focus-visible + .check-chip-label{outline:2px solid rgba(239,186,112,.6);outline-offset:2px}
            .check-chip-input:checked + .check-chip-label{border-color:#476b3f;background:linear-gradient(135deg,rgba(43,76,49,.94),rgba(70,108,64,.95));color:#f4fff1;box-shadow:0 10px 24px rgba(28,52,34,.32)}
            .notice{display:none;padding:1rem 1.1rem;margin-bottom:1rem;border-radius:18px}
            .notice.show{display:block}.notice.error{color:#ffd9d9;border-color:#7b4a4a;background:rgba(123,74,74,.18)}.notice.success{color:#d7f0dc;border-color:#4d7556;background:rgba(77,117,86,.18)}
            .empty{padding:2rem;text-align:center}
            .stack{display:grid;gap:1rem}
            .rule-block{padding:1rem;border-radius:18px;background:var(--soft);border:1px solid var(--line)}
            .rule-block ul{margin:.7rem 0 0;padding-left:1rem;color:var(--muted)}
            .rule-block li{margin:.3rem 0}
            .wizard-log{display:grid;gap:.8rem;max-height:520px;overflow:auto;padding-right:.2rem}
            .wizard-message{padding:1rem;border-radius:18px;border:1px solid var(--line);background:var(--soft)}
            .wizard-message.user{background:rgba(213,131,69,.12);border-color:#8b623f}
            .wizard-speaker{margin-bottom:.45rem;font-size:.78rem;letter-spacing:.12em;text-transform:uppercase;color:var(--accent2)}
            .wizard-message p{margin:0}
            .wizard-form{display:grid;grid-template-columns:1fr auto;gap:.75rem;margin-top:1rem}
            .wizard-actions{display:flex;flex-wrap:wrap;gap:.55rem;margin-top:1rem}
            .wizard-actions.rich{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
            .wizard-option{display:grid;gap:.25rem;justify-items:start;text-align:left;border-radius:18px;padding:1rem !important}
            .wizard-option strong{color:var(--text);font-size:1rem}
            .dice-buttons{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem;margin-top:1rem}
            .dice-form{display:grid;grid-template-columns:minmax(0,1fr) 180px auto;gap:.75rem;margin-top:1rem}
            .dice-result-card{padding:1.1rem;border-radius:18px;background:var(--soft);border:1px solid var(--line)}
            .dice-result-card h3{margin-bottom:.45rem}
            .dice-result-card p{margin:.3rem 0 0}
            .dice-inline{display:flex;flex-wrap:wrap;gap:.55rem;margin:.9rem 0 .2rem}
            .wizard-summary-card{padding:1rem;border-radius:18px;background:var(--soft);border:1px solid var(--line)}
            .wizard-summary-card h3{margin-bottom:.45rem}
            .wizard-summary-card ul{margin:.6rem 0 0;padding-left:1rem;color:var(--muted)}
            .wizard-summary-card li{margin:.25rem 0}
            .hover-help{padding:.95rem 1rem;border-radius:18px;border:1px solid rgba(139,98,63,.7);background:rgba(255,255,255,.04);color:var(--muted)}
            .hover-help strong{display:block;margin-bottom:.35rem;color:var(--accent2);font-size:.82rem;letter-spacing:.08em;text-transform:uppercase}
            .summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
            .library-toolbar{display:grid;grid-template-columns:280px 1fr;gap:1rem;margin-bottom:1rem}
            .library-stage{padding:1rem;border-radius:24px;border:1px solid rgba(78,55,42,.72);background:linear-gradient(180deg,rgba(58,41,32,.34),rgba(34,25,21,.18));box-shadow:inset 0 1px 0 rgba(255,255,255,.03);backdrop-filter:blur(2px);overflow:hidden}
            .library-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;align-content:start}
            .library-results{column-width:320px;column-gap:1rem}
            .library-results > .library-card,.library-results > .empty{display:inline-block;vertical-align:top;width:100%;margin:0 0 1rem;break-inside:avoid}
            .library-card{padding:1rem;border-radius:18px;background:var(--soft);border:1px solid var(--line)}
            .chip-list{display:flex;flex-wrap:wrap;gap:.45rem;margin-top:.85rem}
            .chip{padding:.35rem .6rem;border-radius:999px;background:rgba(255,255,255,.05);border:1px solid var(--line);font-size:.82rem;color:var(--muted)}
            .chip-action{appearance:none;cursor:pointer}
            .chip-action:hover{border-color:#8b623f;color:var(--text)}
            .entry-meta{margin-top:.8rem;color:var(--muted);font-size:.88rem}
            .entry-list{margin:.75rem 0 0;padding-left:1rem;color:var(--muted)}
            .entry-list li{margin:.3rem 0}
            .tiny{font-size:.92rem;color:rgba(247,234,216,.65)}
            #forge{order:2}
            #dice{order:3}
            #wizard{order:4}
            #library{order:5}
            @media (max-width:980px){.hero,.grid,.library-grid,.library-toolbar,.summary-grid,.quick{grid-template-columns:1fr}.topbar{position:static;border-radius:28px;align-items:stretch}.nav{justify-content:center}}
            @media (max-width:720px){.section-grid,.stats,.quick,.check-grid{grid-template-columns:1fr}.topbar{border-radius:26px;padding:1rem}.brand{justify-content:center}.nav a{flex:1 1 calc(50% - .6rem);text-align:center}.head{flex-direction:column;align-items:start}}
            @media (max-width:720px){.dice-buttons,.dice-form{grid-template-columns:1fr}}
            @media (max-width:640px){.wrap{width:min(100% - 1rem,100%)}.topbar,.card,.panel{padding:1.05rem}h1{max-width:100%}.library-results{column-width:auto;columns:1}}
        </style>
    </head>
    <body>
        <div class="wrap">
            <header class="topbar">
                <a class="brand" href="#top"><span class="mark">D20</span><span>Adventurer's Ledger</span></a>
                <nav class="nav">
                    <a href="#forge">Builder</a>
                    <a href="#dice">Dice</a>
                    <a href="#wizard">Wizard</a>
                    <a href="{{ route('roster') }}">Roster</a>
                    <a href="#library">Library</a>
                    <a href="/api/compendium">Rules API</a>
                </nav>
            </header>

            <main id="top">
                <section class="hero">
                    <article class="card">
                        <span class="eyebrow">Character Builder</span>
                        <h1>Build a character and keep the whole sheet in one place.</h1>
                        <p>Create a full character sheet, roll stats, save your roster, and browse the rules without bouncing between tools.</p>
                        <div class="hero-actions">
                            <a class="btn" href="#forge">Open builder</a>
                            <a class="btn-soft" href="{{ route('roster') }}">Open roster</a>
                        </div>
                    </article>
                </section>

                <section class="panel">
                    <div class="head">
                        <div>
                            <span class="eyebrow">Live Snapshot</span>
                            <h2>Everything in one place</h2>
                        </div>
                    </div>
                    <p>Keep the current roster size, latest roll, and rules index in view while you build.</p>
                    <div class="quick">
                        <div class="mini">Characters<strong id="count">0</strong></div>
                        <div class="mini">Latest Roll<strong id="latest-roll">Ready</strong></div>
                        <div class="mini">Library<strong>{{ count(config('dnd.compendium_sections', [])) }} sections</strong></div>
                        <div class="mini">Verified<strong>{{ config('dnd.verified_at') }}</strong></div>
                    </div>
                </section>

                <section class="stack" id="wizard">
                    <div class="panel">
                        <div class="head">
                            <div>
                                <span class="eyebrow">Rules Wizard</span>
                                <h2>Chat-style character guide</h2>
                            </div>
                            <button class="btn-soft" id="wizard-reset" type="button">Reset wizard</button>
                        </div>
                        <p>Use the wizard to build a character step by step, check level gains, track combat resources, roll dice, and look up monsters without leaving the page.</p>
                        <div class="notice" id="wizard-notice"></div>
                        <div class="wizard-log" id="wizard-log"></div>
                        <div class="wizard-actions" id="wizard-actions"></div>
                        <div class="hover-help" id="wizard-action-help" hidden>
                            <strong>Option Details</strong>
                            <span id="wizard-action-help-text">Hover a wizard option to see what it means.</span>
                        </div>

                        <form class="wizard-form" id="wizard-form">
                            <input id="wizard-input" type="text" placeholder="Type a command like new character, roll d20+5, show summary, or level up">
                            <button class="btn" type="submit">Send</button>
                        </form>
                    </div>

                    <div class="panel">
                        <span class="eyebrow">Wizard Support</span>
                        <div class="rule-block">
                            <h3>Ability Preview</h3>
                            <div class="stats" id="preview">
                                <div class="stat"><span class="label">STR</span><span class="value">-</span></div>
                                <div class="stat"><span class="label">DEX</span><span class="value">-</span></div>
                                <div class="stat"><span class="label">CON</span><span class="value">-</span></div>
                                <div class="stat"><span class="label">INT</span><span class="value">-</span></div>
                                <div class="stat"><span class="label">WIS</span><span class="value">-</span></div>
                                <div class="stat"><span class="label">CHA</span><span class="value">-</span></div>
                            </div>
                        </div>
                        <div class="rule-block" style="margin-top:1rem">
                            <h3>Wizard Snapshot</h3>
                            <div class="stack" id="wizard-summary">
                                <div class="wizard-summary-card">
                                    <h3>No active character</h3>
                                    <p>The wizard will show the current sheet, combat state, features, and next-level preview here.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rule-block" style="margin-top:1rem">
                            <h3>Try Asking</h3>
                            <input id="try-asking-search" type="text" placeholder="Search example commands like roll, rest, or summary">
                            <div class="chip-list" id="try-asking-chips">
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="new character">new character</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="list characters">list characters</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="load latest">load latest</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="show summary">show summary</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="show status">show status</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="what did I gain">what did I gain</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="show next">show next</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="level up">level up</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="show spells">show spells</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="roll d20">roll d20</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="roll 2d6+3">roll 2d6+3</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="help me roleplay">help me roleplay</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="show appearance help">show appearance help</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="roll initiative">roll initiative</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="short rest 2">short rest 2</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="show monster goblin">show monster goblin</button>
                                <button class="chip chip-action" type="button" data-try-chip data-try-command="save character">save character</button>
                            </div>
                            <p class="tiny" id="try-asking-empty" hidden>No example commands match that search yet.</p>
                        </div>
                        <div class="rule-block" style="margin-top:1rem">
                            <h3>Wizard Dice</h3>
                            <p>The wizard understands custom rolls like <code>roll d20</code> or <code>roll 2d6+3</code>. These shortcuts send the command straight into the wizard log.</p>
                            <div class="dice-inline" id="wizard-dice-buttons">
                                <button class="btn-soft" type="button" data-wizard-command="roll d4">d4</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll d6">d6</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll d8">d8</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll d10">d10</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll d12">d12</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll d20">d20</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll d20 advantage">Adv d20</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll d20 disadvantage">Dis d20</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll d100">d100</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll 2d6+3">2d6+3</button>
                                <button class="btn-soft" type="button" data-wizard-command="roll stats">Roll Stats</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="stack" id="forge">
                    <div class="panel">
                        <span class="eyebrow">Character Builder</span>
                        <h2>Build a character</h2>
                        <p>Work through the sheet in a clear order: class, origin, ability scores, alignment, then the extra details that round the character out.</p>
                        <div class="notice" id="form-notice"></div>

                        <form id="character-form">
                            <div class="form">
                                <div class="form-section">
                                    <div class="section-head">
                                        <span class="section-kicker">Step 1</span>
                                        <h3>Choose a class</h3>
                                        <p>Class is the biggest gameplay choice. It sets your role, your main tactics, and what kinds of scores matter most. Everything in this step is required.</p>
                                    </div>
                                    <div class="section-grid">
                                        <label>
                                            <span>Class</span>
                                            <select id="class" name="class" required>
                                                <option value="">Choose a class</option>
                                                @foreach (config('dnd.classes', []) as $class)
                                                    <option value="{{ $class }}">{{ $class }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>
                                            <span>Level</span>
                                            <input id="level" name="level" type="number" min="1" value="1" required>
                                        </label>
                                        <label class="full">
                                            <span>Subclass</span>
                                            <select id="subclass" name="subclass" required>
                                                <option value="">Choose a class first</option>
                                            </select>
                                        </label>
                                    </div>
                                    <div class="hover-help" id="step1-choice-help" hidden>
                                        <strong>Step 1 Details</strong>
                                        <span>Hover class and subclass choices to preview them here.</span>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="section-head">
                                        <span class="section-kicker">Step 2</span>
                                        <h3>Determine origin</h3>
                                        <p>Origin covers the parts your character brings into the adventure: background, species, languages, and the feat that came with that early life. Everything in this step is required.</p>
                                    </div>
                                    <div class="section-grid">
                                        <label>
                                            <span>Name</span>
                                            <input id="name" name="name" required placeholder="Rin, Mara, Toren...">
                                            <span class="tiny" id="name-placeholder-note">Name ideas can shift with species, but any name is valid.</span>
                                        </label>
                                        <label>
                                            <span>Background</span>
                                            <select id="background" name="background" required>
                                                <option value="">Choose a background</option>
                                                @foreach (config('dnd.backgrounds', []) as $background)
                                                    <option value="{{ $background }}">{{ $background }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>
                                            <span>Species</span>
                                            <select id="species" name="species" required>
                                                <option value="">Choose a species</option>
                                                @foreach (config('dnd.species', []) as $species)
                                                    <option value="{{ $species }}">{{ $species }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label>
                                            <span>Origin Feat</span>
                                            <select id="origin_feat" name="origin_feat" required>
                                                <option value="">Choose an origin feat</option>
                                                @foreach (config('dnd.origin_feats', []) as $originFeat)
                                                    <option value="{{ $originFeat }}">{{ $originFeat }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="full">
                                            <span>Languages</span>
                                            <div class="check-grid" id="languages">
                                                @foreach (config('dnd.languages', []) as $language)
                                                    <label class="check-chip">
                                                        <input class="check-chip-input" type="checkbox" name="languages[]" value="{{ $language }}">
                                                        <span class="check-chip-label">{{ $language }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <span class="tiny">Pick at least one language. This is part of the core character sheet.</span>
                                        </label>
                                    </div>
                                    <div class="hover-help" id="step2-choice-help" hidden>
                                        <strong>Step 2 Details</strong>
                                        <span>Hover origin choices to preview them here.</span>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="section-head">
                                        <span class="section-kicker">Step 3</span>
                                        <h3>Determine ability scores</h3>
                                        <p>This is the mechanical heart of the sheet, so it comes before personality notes and appearance details. Everything in this step is required.</p>
                                    </div>
                                    <div class="section-grid">
                                        <label><span>Strength</span><input id="strength" name="strength" type="number" min="3" max="18" required></label>
                                        <label><span>Dexterity</span><input id="dexterity" name="dexterity" type="number" min="3" max="18" required></label>
                                        <label><span>Constitution</span><input id="constitution" name="constitution" type="number" min="3" max="18" required></label>
                                        <label><span>Intelligence</span><input id="intelligence" name="intelligence" type="number" min="3" max="18" required></label>
                                        <label><span>Wisdom</span><input id="wisdom" name="wisdom" type="number" min="3" max="18" required></label>
                                        <label><span>Charisma</span><input id="charisma" name="charisma" type="number" min="3" max="18" required></label>
                                    </div>
                                    <div class="actions" style="margin-top:1rem">
                                        <button class="btn" id="roll-btn" type="button">Roll ability scores</button>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="section-head">
                                        <span class="section-kicker">Step 4</span>
                                        <h3>Choose an alignment</h3>
                                        <p>Alignment is a small shorthand for outlook. It helps guide roleplay, but it should not override the rest of the character. This step is optional.</p>
                                    </div>
                                    <div class="section-grid">
                                        <label class="full">
                                            <span>Alignment</span>
                                            <select id="alignment" name="alignment">
                                                <option value="">Choose an alignment</option>
                                                @foreach (config('dnd.alignments', []) as $alignment)
                                                    <option value="{{ $alignment }}">{{ $alignment }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    </div>
                                    <div class="hover-help" id="step4-choice-help" hidden>
                                        <strong>Step 4 Details</strong>
                                        <span>Hover the alignment field to preview the current choice here.</span>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="section-head">
                                        <span class="section-kicker">Step 5</span>
                                        <h3>Fill in details</h3>
                                        <p>Finish the sheet with the optional details that make the character easier to picture and easier to play. Everything in this step is skippable.</p>
                                    </div>
                                    <div class="section-grid">
                                        <label class="full">
                                            <span>Personality Traits</span>
                                            <textarea id="personality_traits" name="personality_traits" placeholder="Short first-impression notes like calm, curious, dry humor..."></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.personality_traits') }}</span>
                                        </label>
                                        <label class="full">
                                            <span>Ideals</span>
                                            <textarea id="ideals" name="ideals" placeholder="What principle matters most to this character?"></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.ideals') }}</span>
                                        </label>
                                        <label class="full">
                                            <span>Bonds</span>
                                            <textarea id="bonds" name="bonds" placeholder="Who or what matters enough to change their decisions?"></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.bonds') }}</span>
                                        </label>
                                        <label class="full">
                                            <span>Flaws</span>
                                            <textarea id="flaws" name="flaws" placeholder="What weakness or habit tends to cause trouble?"></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.flaws') }}</span>
                                        </label>
                                        <div class="full tiny" id="roleplay-placeholder-note">Roleplay prompts adapt to alignment, class, and background. They are examples, not limits.</div>
                                        <label><span>Age</span><input id="age" name="age" placeholder="23 or 120"></label>
                                        <label><span>Height</span><input id="height" name="height" placeholder="173 cm or 5ft 8in"></label>
                                        <label><span>Weight</span><input id="weight" name="weight" placeholder="72 kg or 160 lb"></label>
                                        <label><span>Eyes</span><input id="eyes" name="eyes" placeholder="Gray, green, amber..."></label>
                                        <label><span>Hair</span><input id="hair" name="hair" placeholder="Black braid, copper curls..."></label>
                                        <label><span>Skin</span><input id="skin" name="skin" placeholder="Olive, freckled, scarred..."></label>
                                        <div class="full tiny" id="appearance-placeholder-note">Appearance examples shift with species. They are lore-style examples, not hard limits.</div>
                                        <label class="full"><span>Notes</span><textarea id="notes" name="notes" placeholder="Campaign notes, hooks, gear, personality..."></textarea></label>
                                    </div>
                                </div>
                            </div>
                            <div class="actions" style="margin-top:1rem">
                                <button class="btn-soft" id="random-character-btn" type="button">Random character</button>
                                <button class="btn-soft" id="clear-btn" type="button">Clear form</button>
                                <button class="btn" type="submit">Save character</button>
                            </div>
                        </form>
                    </div>

                    <div class="panel">
                        <span class="eyebrow">Build Guide</span>
                        <div class="summary-grid">
                            <div class="rule-block">
                                <h3 id="selected-build-title">Start the build</h3>
                                <p id="selected-build-summary">Work top to bottom: class, origin, ability scores, alignment, then the rest of the sheet.</p>
                                <p id="selected-build-focus" class="tiny">This panel updates live so you can see what is already covered and what still needs attention.</p>
                                <ul id="selected-build-checklist"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-class-title">Choose a class</h3>
                                <p id="selected-class-summary">Class notes appear here once you make a choice.</p>
                                <p id="selected-class-focus" class="tiny">Primary focus and playstyle notes will appear here.</p>
                                <ul id="selected-subclasses"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-background-title">Choose a background</h3>
                                <p id="selected-background-summary">Background notes appear here once you make a choice.</p>
                                <p id="selected-background-theme" class="tiny">Background themes and roleplay hooks will appear here.</p>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-species-title">Choose a species</h3>
                                <p id="selected-species-summary">Species notes appear here once you make a choice.</p>
                                <ul id="selected-species-traits"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-origin-feat-title">Choose an origin feat</h3>
                                <p id="selected-origin-feat-summary">Feat notes appear here once you make a choice.</p>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-languages-title">Choose languages</h3>
                                <ul id="selected-language-list"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-stats-title">Ability score guide</h3>
                                <p id="selected-stats-summary">Roll or enter scores to see modifiers, class-fit advice, and where the build is strongest.</p>
                                <ul id="selected-stats-list"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-alignment-title">Choose an alignment</h3>
                                <p id="selected-alignment-summary">Your selected alignment summary will appear here.</p>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-roleplay-title">Beginner roleplay help</h3>
                                <p id="selected-roleplay-summary">Pick one alignment and a few short personality anchors. You do not need to write a novel.</p>
                                <ul id="selected-roleplay-list"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-appearance-title">Appearance help</h3>
                                <p id="selected-appearance-summary">A few visual anchors are enough: age, height, eyes, hair, and one memorable detail.</p>
                                <ul id="selected-appearance-list"></ul>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="stack" id="dice">
                    <div class="panel">
                        <div class="head">
                            <div>
                                <span class="eyebrow">Dice Tray</span>
                                <h2>Roll outside the wizard</h2>
                            </div>
                        </div>
                        <p>Use the tray when you just need fast dice. It supports the full standard set, custom expressions like <code>2d6+3</code>, and a full six-stat roll.</p>
                        <div class="notice" id="dice-notice"></div>
                        <div class="dice-buttons" id="dice-buttons">
                            <button class="btn-soft" type="button" data-dice-expression="d4">d4</button>
                            <button class="btn-soft" type="button" data-dice-expression="d6">d6</button>
                            <button class="btn-soft" type="button" data-dice-expression="d8">d8</button>
                            <button class="btn-soft" type="button" data-dice-expression="d10">d10</button>
                            <button class="btn-soft" type="button" data-dice-expression="d12">d12</button>
                            <button class="btn-soft" type="button" data-dice-expression="d20">d20</button>
                            <button class="btn-soft" type="button" data-dice-expression="d20" data-dice-mode="advantage">Adv d20</button>
                            <button class="btn-soft" type="button" data-dice-expression="d20" data-dice-mode="disadvantage">Dis d20</button>
                            <button class="btn-soft" type="button" data-dice-expression="d100">d100</button>
                            <button class="btn-soft" type="button" data-dice-expression="2d6+3">2d6+3</button>
                            <button class="btn-soft" type="button" data-dice-expression="1d8+2">1d8+2</button>
                            <button class="btn-soft" id="dice-roll-stats" type="button">Roll Stats</button>
                        </div>
                        <form class="dice-form" id="dice-form">
                            <input id="dice-expression" type="text" placeholder="Custom roll like 2d6+3, d20, or 4d8-1">
                            <select id="dice-mode">
                                <option value="">Normal</option>
                                <option value="advantage">Advantage</option>
                                <option value="disadvantage">Disadvantage</option>
                            </select>
                            <button class="btn" type="submit">Roll</button>
                        </form>
                        <div class="actions" style="margin-top:1rem">
                            <button class="btn-soft" id="dice-clear" type="button">Clear tray</button>
                        </div>
                    </div>

                    <div class="panel">
                        <span class="eyebrow">Result</span>
                        <div class="dice-result-card" id="dice-result">
                            <h3>Ready to roll</h3>
                            <p>Pick any die, use a custom expression, or roll full ability scores from here.</p>
                        </div>
                    </div>
                </section>

                <section class="panel" id="library">
                    <div class="head">
                        <div>
                            <span class="eyebrow">Rules Library</span>
                            <h2>Browse the rules reference</h2>
                        </div>
                    </div>

                    <p>Use the section list and search box to find classes, spells, monsters, equipment, and other rules entries.</p>

                    <div class="library-toolbar">
                        <select id="compendium-section">
                            @foreach (config('dnd.compendium_sections', []) as $section)
                                <option value="{{ $section['key'] }}">{{ $section['title'] }} ({{ $section['count'] }})</option>
                            @endforeach
                        </select>
                        <input id="compendium-search" type="text" placeholder="Search names, summaries, traits, or tags...">
                    </div>

                    <div class="notice" id="compendium-notice"></div>
                    <div class="library-card" style="margin-bottom:1rem">
                        <h3 id="compendium-title">Rules Reference</h3>
                        <p id="compendium-summary" class="tiny">Section summary</p>
                    </div>
                    <div class="library-stage">
                        <div class="library-results" id="compendium-results"></div>
                    </div>
                </section>
            </main>
        </div>

        @php
            $pageData = [
                'configurator' => [
                    'class_details' => config('dnd.class_details'),
                    'species_details' => config('dnd.species_details'),
                    'background_details' => config('dnd.background_details'),
                    'ability_details' => config('dnd.ability_details'),
                    'alignment_details' => config('dnd.alignment_details'),
                    'alignment_roleplay' => config('dnd.alignment_roleplay'),
                    'roleplay_field_help' => config('dnd.roleplay_field_help'),
                    'origin_feat_details' => config('dnd.origin_feat_details'),
                    'language_details' => config('dnd.language_details'),
                    'appearance_field_help' => config('dnd.appearance_field_help'),
                    'form_placeholder_profiles' => config('dnd.form_placeholder_profiles'),
                    'ability_appearance_cues' => config('dnd.ability_appearance_cues'),
                ],
                'compendium' => config('dnd.compendium'),
                'compendium_sections' => array_values(config('dnd.compendium_sections')),
            ];
        @endphp
        <script id="page-data" type="application/json">{!! json_encode($pageData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script>
            const pageData = JSON.parse(document.getElementById('page-data').textContent);
            const configurator = pageData.configurator;
            const compendium = pageData.compendium;
            const compendiumSections = pageData.compendium_sections;
            const countEl = document.getElementById('count');
            const rollEl = document.getElementById('latest-roll');
            const formNotice = document.getElementById('form-notice');
            const wizardNotice = document.getElementById('wizard-notice');
            const diceNotice = document.getElementById('dice-notice');
            const compendiumNotice = document.getElementById('compendium-notice');
            const previewEl = document.getElementById('preview');
            const wizardActionHelp = document.getElementById('wizard-action-help');
            const wizardActionHelpText = document.getElementById('wizard-action-help-text');
            const form = document.getElementById('character-form');
            const wizardForm = document.getElementById('wizard-form');
            const wizardInput = document.getElementById('wizard-input');
            const wizardLog = document.getElementById('wizard-log');
            const wizardActions = document.getElementById('wizard-actions');
            const wizardSummary = document.getElementById('wizard-summary');
            const tryAskingSearch = document.getElementById('try-asking-search');
            const tryAskingContainer = document.getElementById('try-asking-chips');
            const tryAskingChips = Array.from(document.querySelectorAll('[data-try-chip]'));
            const tryAskingEmpty = document.getElementById('try-asking-empty');
            const diceForm = document.getElementById('dice-form');
            const diceExpressionInput = document.getElementById('dice-expression');
            const diceModeSelect = document.getElementById('dice-mode');
            const diceButtons = document.getElementById('dice-buttons');
            const diceResult = document.getElementById('dice-result');
            const wizardDiceButtons = document.querySelectorAll('[data-wizard-command]');
            const nameInput = document.getElementById('name');
            const levelInput = document.getElementById('level');
            const classSelect = document.getElementById('class');
            const subclassSelect = document.getElementById('subclass');
            const speciesSelect = document.getElementById('species');
            const backgroundSelect = document.getElementById('background');
            const alignmentSelect = document.getElementById('alignment');
            const originFeatSelect = document.getElementById('origin_feat');
            const languageInputs = Array.from(document.querySelectorAll('input[name="languages[]"]'));
            const personalityTraitsInput = document.getElementById('personality_traits');
            const idealsInput = document.getElementById('ideals');
            const bondsInput = document.getElementById('bonds');
            const flawsInput = document.getElementById('flaws');
            const ageInput = document.getElementById('age');
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            const eyesInput = document.getElementById('eyes');
            const hairInput = document.getElementById('hair');
            const skinInput = document.getElementById('skin');
            const notesInput = document.getElementById('notes');
            const namePlaceholderNote = document.getElementById('name-placeholder-note');
            const roleplayPlaceholderNote = document.getElementById('roleplay-placeholder-note');
            const appearancePlaceholderNote = document.getElementById('appearance-placeholder-note');
            const step1ChoiceHelp = document.getElementById('step1-choice-help');
            const step2ChoiceHelp = document.getElementById('step2-choice-help');
            const step4ChoiceHelp = document.getElementById('step4-choice-help');
            const compendiumSectionSelect = document.getElementById('compendium-section');
            const compendiumSearchInput = document.getElementById('compendium-search');
            const compendiumTitle = document.getElementById('compendium-title');
            const compendiumSummary = document.getElementById('compendium-summary');
            const compendiumResults = document.getElementById('compendium-results');
            const statFields = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            const optionalTextFields = ['alignment', 'personality_traits', 'ideals', 'bonds', 'flaws', 'age', 'height', 'weight', 'eyes', 'hair', 'skin', 'notes'];
            let wizardState = {};

            function notice(el, message, type) {
                el.textContent = message;
                el.className = `notice show ${type}`;
            }

            function clearNotice(el) {
                el.textContent = '';
                el.className = 'notice';
            }

            function showHoverHelp(box, title, text) {
                if (! box || ! text) {
                    if (box) box.hidden = true;
                    return;
                }

                box.innerHTML = `<strong>${escapeHtml(title)}</strong><span>${escapeHtml(text)}</span>`;
                box.hidden = false;
            }

            function hideHoverHelp(box) {
                if (box) box.hidden = true;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#39;');
            }

            function renderPreview(stats) {
                const labels = ['STR', 'DEX', 'CON', 'INT', 'WIS', 'CHA'];
                const values = stats ? [stats.strength, stats.dexterity, stats.constitution, stats.intelligence, stats.wisdom, stats.charisma] : ['-', '-', '-', '-', '-', '-'];
                previewEl.innerHTML = labels.map((label, index) => `<div class="stat"><span class="label">${label}</span><span class="value">${values[index]}</span></div>`).join('');
            }

            function abilityFieldLabel(field) {
                const labels = {
                    strength: 'Strength',
                    dexterity: 'Dexterity',
                    constitution: 'Constitution',
                    intelligence: 'Intelligence',
                    wisdom: 'Wisdom',
                    charisma: 'Charisma',
                };

                return labels[field] || field;
            }

            function abilityModifier(score) {
                return Math.floor((Number(score) - 10) / 2);
            }

            function formatModifier(modifier) {
                return modifier >= 0 ? `+${modifier}` : String(modifier);
            }

            function currentAppearanceCues() {
                const scores = statFields.map((field) => ({
                    field,
                    value: Number(document.getElementById(field).value),
                })).filter((entry) => Number.isFinite(entry.value) && entry.value > 0);

                if (! scores.length) return [];

                const highest = [...scores].sort((a, b) => b.value - a.value)[0];
                const lowest = [...scores].sort((a, b) => a.value - b.value)[0];
                const cues = [];

                if (highest) {
                    const label = abilityFieldLabel(highest.field);
                    const words = configurator.ability_appearance_cues?.[label]?.high || [];
                    if (words.length) cues.push(`High ${label} cue: ${words.join(', ')}`);
                }

                if (lowest && lowest.field !== highest.field) {
                    const label = abilityFieldLabel(lowest.field);
                    const words = configurator.ability_appearance_cues?.[label]?.low || [];
                    if (words.length) cues.push(`Low ${label} cue: ${words.join(', ')}`);
                }

                return cues;
            }

            function backgroundDrivenBondPlaceholder(backgroundValue, fallback) {
                const theme = configurator.background_details?.[backgroundValue]?.theme;

                if (! theme) return fallback;

                return `A person, place, or promise tied to ${theme.toLowerCase()}...`;
            }

            function classDrivenTraitPlaceholder(classValue, fallback) {
                const suggestions = {
                    Barbarian: 'Blunt, intense, protective, hard to intimidate...',
                    Bard: 'Warm, theatrical, teasing, always with a story...',
                    Cleric: 'Steady, observant, compassionate, quietly certain...',
                    Druid: 'Grounded, patient, weather-wise, hard to rush...',
                    Fighter: 'Disciplined, practical, alert, built for pressure...',
                    Monk: 'Calm, focused, restrained, always measuring the room...',
                    Paladin: 'Earnest, resolute, inspiring, impossible to ignore...',
                    Ranger: 'Watchful, dry-humored, capable, always tracking something...',
                    Rogue: 'Quick-eyed, guarded, clever, never fully off-balance...',
                    Sorcerer: 'Intense, instinctive, magnetic, power close to the surface...',
                    Warlock: 'Measured, uncanny, confident, keeping a private edge...',
                    Wizard: 'Curious, precise, distracted, always connecting patterns...',
                };

                return suggestions[classValue] || fallback;
            }

            function updateAdaptivePlaceholders() {
                const profiles = configurator.form_placeholder_profiles || {};
                const defaultProfile = profiles.default || {};
                const speciesProfile = profiles.species?.[speciesSelect.value] || {};
                const alignmentRoleplay = configurator.alignment_roleplay?.[alignmentSelect.value] || {};
                const backgroundTheme = configurator.background_details?.[backgroundSelect.value]?.theme;
                const focus = configurator.class_details?.[classSelect.value]?.primary_focus || [];

                const mergedProfile = {
                    ...defaultProfile,
                    ...speciesProfile,
                };

                nameInput.placeholder = mergedProfile.name || 'Rin, Mara, Toren...';
                ageInput.placeholder = mergedProfile.age || '19, 42, or 120';
                heightInput.placeholder = mergedProfile.height || '173 cm or 5ft 8in';
                weightInput.placeholder = mergedProfile.weight || '72 kg or 160 lb';
                eyesInput.placeholder = mergedProfile.eyes || 'Gray, green, amber...';
                hairInput.placeholder = mergedProfile.hair || 'Black braid, copper curls...';
                skinInput.placeholder = mergedProfile.skin || 'Olive, freckled, scarred...';

                personalityTraitsInput.placeholder = alignmentRoleplay.starter_trait
                    || classDrivenTraitPlaceholder(classSelect.value, 'Short first-impression notes like calm, curious, dry humor...');
                idealsInput.placeholder = alignmentRoleplay.starter_ideal
                    || (backgroundTheme ? `${backgroundTheme} matters more than comfort.` : 'What principle matters most to this character?');
                bondsInput.placeholder = alignmentRoleplay.starter_bond
                    || backgroundDrivenBondPlaceholder(backgroundSelect.value, 'Who or what matters enough to change their decisions?');
                flawsInput.placeholder = alignmentRoleplay.starter_flaw
                    || (classSelect.value ? `A ${classSelect.value.toLowerCase()} habit that sometimes causes trouble...` : 'What weakness or habit tends to cause trouble?');

                const notesBits = [
                    classSelect.value ? `${classSelect.value} hooks` : '',
                    backgroundSelect.value ? `${backgroundSelect.value} ties` : '',
                    speciesSelect.value ? `${speciesSelect.value} details` : '',
                    focus.length ? `${focus.join('/')} focus` : '',
                ].filter(Boolean);
                notesInput.placeholder = notesBits.length
                    ? `${notesBits.join(', ')}, gear, party links...`
                    : 'Campaign notes, hooks, gear, personality...';

                if (namePlaceholderNote) {
                    namePlaceholderNote.textContent = speciesSelect.value
                        ? `Name examples are flavored for ${speciesSelect.value}, but any name is still valid.`
                        : (defaultProfile.name_note || 'Name ideas can shift with species, but any name is valid.');
                }

                if (roleplayPlaceholderNote) {
                    const sources = [
                        alignmentSelect.value ? `alignment (${alignmentSelect.value})` : '',
                        classSelect.value ? `class (${classSelect.value})` : '',
                        backgroundSelect.value ? `background (${backgroundSelect.value})` : '',
                    ].filter(Boolean);

                    roleplayPlaceholderNote.textContent = sources.length
                        ? `Roleplay prompts are borrowing from ${sources.join(', ')}. Treat them as examples, not limits.`
                        : 'Roleplay prompts adapt to alignment, class, and background. They are examples, not limits.';
                }

                if (appearancePlaceholderNote) {
                    appearancePlaceholderNote.textContent = speciesSelect.value
                        ? `Appearance examples are flavored for ${speciesSelect.value}. They are lore-style examples, not hard limits.`
                        : (defaultProfile.appearance_note || 'Appearance examples shift with species. They are lore-style examples, not hard limits.');
                }
            }

            function setLatestRoll(label) {
                rollEl.textContent = String(label || 'Ready').slice(0, 28);
            }

            function renderDiceResultCard(title, lines = []) {
                diceResult.innerHTML = `
                    <h3>${escapeHtml(title)}</h3>
                    ${lines.map((line) => `<p>${escapeHtml(line)}</p>`).join('')}
                `;
            }

            function renderDiceExpressionResult(data) {
                const modeLabel = data.mode ? ` (${data.mode})` : '';
                renderDiceResultCard(
                    `${data.expression}${modeLabel} = ${data.total}`,
                    [data.detail || 'Roll complete.'],
                );
                setLatestRoll(`${data.expression}: ${data.total}`);
            }

            function renderDiceStatsResult(stats, { populateForm = false } = {}) {
                if (populateForm) {
                    statFields.forEach((field) => document.getElementById(field).value = stats[field]);
                    renderPreview(stats);
                }

                const summary = statFields
                    .map((field) => `${field.slice(0, 3).toUpperCase()} ${stats[field]}`)
                    .join(' | ');

                renderDiceResultCard('Ability Scores Rolled', [summary, 'Rolled as 4d6 and dropped the lowest die for each stat.']);
                setLatestRoll(summary);
            }

            function appendWizardMessage(role, text) {
                const article = document.createElement('article');
                article.className = `wizard-message ${role}`;
                article.innerHTML = `
                    <div class="wizard-speaker">${role === 'user' ? 'You' : 'Rules Wizard'}</div>
                    <p>${escapeHtml(text).replaceAll('\n', '<br>')}</p>
                `;
                wizardLog.appendChild(article);
                wizardLog.scrollTop = wizardLog.scrollHeight;
            }

            function wizardActionDescription(action) {
                const pendingField = wizardState?.pending_field;

                if (! pendingField) return '';
                if (action === 'skip') return 'Leave this optional field blank for now.';
                if (action === 'skip all details') return 'Finish the core draft now and come back to optional details later.';

                if (pendingField === 'species') {
                    return configurator.species_details?.[action]?.summary || '';
                }

                if (pendingField === 'class') {
                    const detail = configurator.class_details?.[action];
                    if (! detail) return '';
                    return [detail.summary, Array.isArray(detail.primary_focus) && detail.primary_focus.length ? `Focus: ${detail.primary_focus.join(', ')}` : '']
                        .filter(Boolean)
                        .join(' ');
                }

                if (pendingField === 'background') {
                    const detail = configurator.background_details?.[action];
                    if (! detail) return '';
                    return [detail.summary, detail.theme ? `Theme: ${detail.theme}` : ''].filter(Boolean).join(' ');
                }

                if (pendingField === 'alignment') {
                    return configurator.alignment_details?.[action] || '';
                }

                if (pendingField === 'origin_feat') {
                    return configurator.origin_feat_details?.[action] || '';
                }

                if (pendingField === 'subclass') {
                    return wizardState?.character?.class
                        ? `Subclass option for ${wizardState.character.class}.`
                        : 'Subclass option for the chosen class.';
                }

                return '';
            }

            function formChoiceDescription(field, value) {
                if (! value) return '';

                if (field === 'class') {
                    const detail = configurator.class_details?.[value];
                    return detail
                        ? [detail.summary, Array.isArray(detail.primary_focus) && detail.primary_focus.length ? `Focus: ${detail.primary_focus.join(', ')}` : '']
                            .filter(Boolean)
                            .join(' ')
                        : '';
                }

                if (field === 'subclass') {
                    return classSelect.value
                        ? `${value} is a subclass option for ${classSelect.value}.`
                        : `${value} is a subclass option.`;
                }

                if (field === 'species') {
                    const detail = configurator.species_details?.[value];
                    return detail
                        ? [detail.summary, detail.size ? `Size: ${detail.size}.` : '', detail.speed ? `Speed: ${detail.speed}.` : '']
                            .filter(Boolean)
                            .join(' ')
                        : '';
                }

                if (field === 'background') {
                    const detail = configurator.background_details?.[value];
                    return detail
                        ? [detail.summary, detail.theme ? `Theme: ${detail.theme}.` : '']
                            .filter(Boolean)
                            .join(' ')
                        : '';
                }

                if (field === 'alignment') {
                    return configurator.alignment_details?.[value] || '';
                }

                if (field === 'origin_feat') {
                    return configurator.origin_feat_details?.[value] || '';
                }

                if (field === 'language') {
                    return configurator.language_details?.[value] || '';
                }

                return '';
            }

            function renderWizardActions(actions) {
                wizardActions.innerHTML = '';
                const richActions = actions.some((action) => wizardActionDescription(action));
                wizardActions.classList.toggle('rich', richActions);
                hideHoverHelp(wizardActionHelp);

                actions.slice(0, 12).forEach((action) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `btn-soft${richActions ? ' wizard-option' : ''}`;
                    button.dataset.wizardAction = action;
                    const description = wizardActionDescription(action);
                    button.textContent = action;
                    button.title = description || '';
                    if (description) {
                        button.addEventListener('mouseenter', () => showHoverHelp(wizardActionHelp, action, description));
                        button.addEventListener('focus', () => showHoverHelp(wizardActionHelp, action, description));
                        button.addEventListener('mouseleave', () => hideHoverHelp(wizardActionHelp));
                        button.addEventListener('blur', () => hideHoverHelp(wizardActionHelp));
                    }
                    wizardActions.appendChild(button);
                });
            }

            function syncSelectTitle(control, field) {
                const description = formChoiceDescription(field, control.value);
                control.title = description || '';
            }

            function wireChoiceHelp(control, field, box, titlePrefix) {
                const show = () => {
                    const value = control.value;
                    const description = formChoiceDescription(field, value);
                    syncSelectTitle(control, field);
                    if (! value || ! description) {
                        hideHoverHelp(box);
                        return;
                    }

                    showHoverHelp(box, `${titlePrefix}: ${value}`, description);
                };

                control.addEventListener('mouseenter', show);
                control.addEventListener('focus', show);
                control.addEventListener('change', show);
                control.addEventListener('mouseleave', () => hideHoverHelp(box));
                control.addEventListener('blur', () => hideHoverHelp(box));
            }

            function filterTryAsking() {
                const query = tryAskingSearch.value.trim().toLowerCase();
                let matches = 0;

                tryAskingChips.forEach((chip) => {
                    const visible = ! query || chip.textContent.toLowerCase().includes(query);
                    chip.hidden = ! visible;
                    if (visible) matches += 1;
                });

                tryAskingEmpty.hidden = matches !== 0;
            }

            function renderWizardSummary(snapshot) {
                if (! snapshot || ! snapshot.identity) {
                    wizardSummary.innerHTML = `
                        <div class="wizard-summary-card">
                            <h3>No active character</h3>
                            <p>The wizard will show the current sheet, combat state, features, and next-level preview here.</p>
                        </div>
                    `;
                    return;
                }

                const stats = (snapshot.stats || []).map((stat) => `
                    <li>${escapeHtml(stat.label)}: ${stat.score ?? '-'}${stat.modifier ? ` (${escapeHtml(stat.modifier)})` : ''}</li>
                `).join('');

                const currentFeatures = (snapshot.current_features || []).map((feature) => `<li>${escapeHtml(feature)}</li>`).join('');
                const nextGains = (snapshot.next_gains || []).map((feature) => `<li>${escapeHtml(feature)}</li>`).join('');
                const missing = (snapshot.missing_fields || []).map((field) => `<li>${escapeHtml(field)}</li>`).join('');
                const characterDetails = (snapshot.character_details || []).join(' / ');
                const languages = (snapshot.languages || []).join(', ');
                const roleplay = (snapshot.roleplay || []).map((entry) => `<li>${escapeHtml(entry)}</li>`).join('');
                const appearance = (snapshot.appearance || []).map((entry) => `<li>${escapeHtml(entry)}</li>`).join('');
                const notes = snapshot.notes ? escapeHtml(snapshot.notes) : '';
                const conditions = (snapshot.conditions || []).map((condition) => `<li>Condition: ${escapeHtml(condition)}</li>`).join('');
                const resources = (snapshot.resources || []).map((resource) => `<li>${escapeHtml(resource)}</li>`).join('');
                const concentration = snapshot.concentration ? `<li>Concentration: ${escapeHtml(snapshot.concentration)}</li>` : '';
                const deathTrack = snapshot.death_track ? `<li>${escapeHtml(snapshot.death_track)}</li>` : '';
                const dungeonStatus = snapshot.dungeon_status ? `<li>${escapeHtml(snapshot.dungeon_status)}</li>` : '<li>Dungeon state is waiting on the active build.</li>';

                wizardSummary.innerHTML = `
                    <div class="wizard-summary-card">
                        <h3>${escapeHtml(snapshot.identity)}</h3>
                        <p>${snapshot.proficiency_bonus ? `Proficiency Bonus ${escapeHtml(snapshot.proficiency_bonus)}` : 'Proficiency Bonus pending'}</p>
                        <p>${snapshot.estimated_hit_points !== null ? `Estimated HP ${snapshot.estimated_hit_points}` : 'Estimated HP pending'}</p>
                        <p>${snapshot.spellcasting_summary ? escapeHtml(snapshot.spellcasting_summary) : 'No spellcasting summary yet'}</p>
                        <p>${characterDetails ? escapeHtml(characterDetails) : 'Core identity details like class, species, background, and origin feat are not set yet.'}</p>
                        <p>${languages ? `Languages: ${escapeHtml(languages)}` : 'Languages are not set yet.'}</p>
                        <p>${notes ? `Notes: ${notes}` : 'Notes are not set yet.'}</p>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Dungeon State</h3>
                        <ul>${dungeonStatus}${conditions}${resources}${concentration}${deathTrack}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Stats</h3>
                        <ul>${stats || '<li>No stats set yet.</li>'}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Roleplay</h3>
                        <ul>${roleplay || '<li>No trait, ideal, bond, or flaw set yet.</li>'}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Appearance</h3>
                        <ul>${appearance || '<li>No appearance details set yet.</li>'}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Current Features</h3>
                        <ul>${currentFeatures || '<li>No class features available yet.</li>'}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Next Level Preview</h3>
                        <ul>${nextGains || '<li>No preview available yet.</li>'}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Missing Fields</h3>
                        <ul>${missing || '<li>Build is complete.</li>'}</ul>
                    </div>
                `;
            }

            function renderCompendium() {
                const sectionKey = compendiumSectionSelect.value;
                const searchValue = compendiumSearchInput.value.trim().toLowerCase();
                const section = compendium?.[sectionKey];

                if (! section) {
                    notice(compendiumNotice, 'The requested compendium section was not found.', 'error');
                    compendiumResults.innerHTML = '';
                    return;
                }

                clearNotice(compendiumNotice);
                compendiumTitle.textContent = `${section.title}`;
                compendiumSummary.textContent = sectionKey === 'spells'
                    ? `${section.count} spell entries with level, school, duration, range, and class tags.`
                    : sectionKey === 'monsters'
                        ? `${section.count} monster entries with core combat and stat-block details.`
                    : `${section.count} entries in this section.`;

                const entries = section.items.filter((item) => {
                    const haystack = JSON.stringify(item).toLowerCase();
                    return ! searchValue || haystack.includes(searchValue);
                });

                if (! entries.length) {
                    compendiumResults.innerHTML = `<div class="empty library-card"><span class="eyebrow">No matches</span><h3>No entries match that search.</h3><p>Try a broader keyword or switch to another compendium section.</p></div>`;
                    return;
                }

                compendiumResults.innerHTML = entries.map((item) => {
                    const tags = [];
                    const meta = [];

                    if (item.ability) tags.push(`Ability: ${item.ability}`);
                    if (item.size) tags.push(`Size: ${item.size}`);
                    if (item.creature_type) tags.push(`Type: ${item.creature_type}`);
                    if (item.alignment) tags.push(`Alignment: ${item.alignment}`);
                    if (item.speed) tags.push(`Speed: ${item.speed}`);
                    if (item.theme) tags.push(`Theme: ${item.theme}`);
                    if (Array.isArray(item.primary_focus)) tags.push(`Focus: ${item.primary_focus.join(', ')}`);
                    if (item.level_label) tags.push(`Level: ${item.level_label}`);
                    if (item.school) tags.push(`School: ${item.school}`);
                    if (item.casting_time) tags.push(`Cast: ${item.casting_time}`);
                    if (item.range) tags.push(`Range: ${item.range}`);
                    if (item.ac) tags.push(`AC: ${item.ac}`);
                    if (item.hp) tags.push(`HP: ${item.hp}`);
                    if (item.cr) tags.push(`CR: ${item.cr}`);
                    if (item.initiative) tags.push(`Initiative: ${item.initiative}`);
                    if (item.proficiency_bonus) tags.push(`${item.proficiency_bonus}`);

                    if (item.duration) meta.push(`Duration: ${item.duration}`);
                    if (item.components) meta.push(`Components: ${item.components}`);
                    if (item.attack_save) meta.push(`Attack/Save: ${item.attack_save}`);
                    if (item.damage_effect) meta.push(`Effect: ${item.damage_effect}`);
                    if (item.ritual) meta.push('Ritual');
                    if (item.concentration) meta.push('Concentration');
                    if (Array.isArray(item.classes) && item.classes.length) meta.push(`Classes: ${item.classes.join(', ')}`);
                    if (item.skills) meta.push(`Skills: ${item.skills}`);
                    if (item.senses) meta.push(`Senses: ${item.senses}`);
                    if (item.languages) meta.push(`Languages: ${item.languages}`);
                    if (item.resistances) meta.push(`Resistances: ${item.resistances}`);
                    if (item.immunities) meta.push(`Immunities: ${item.immunities}`);
                    if (item.condition_immunities) meta.push(`Condition Immunities: ${item.condition_immunities}`);
                    if (item.vulnerabilities) meta.push(`Vulnerabilities: ${item.vulnerabilities}`);
                    if (item.gear) meta.push(`Gear: ${item.gear}`);
                    if (item.abilities && typeof item.abilities === 'object') {
                        const scores = ['str', 'dex', 'con', 'int', 'wis', 'cha']
                            .filter((key) => item.abilities[key] !== undefined)
                            .map((key) => `${key.toUpperCase()} ${item.abilities[key]}`);
                        if (scores.length) meta.push(scores.join(' | '));
                    }

                    const bullets = [];
                    if (Array.isArray(item.subclasses)) bullets.push(...item.subclasses.map((entry) => `Subclass: ${entry}`));
                    if (Array.isArray(item.traits)) bullets.push(...item.traits.map((entry) => `Trait: ${entry}`));
                    if (Array.isArray(item.trait_names)) bullets.push(...item.trait_names.map((entry) => `Trait: ${entry}`));
                    if (Array.isArray(item.action_names)) bullets.push(...item.action_names.map((entry) => `Action: ${entry}`));
                    if (Array.isArray(item.bonus_action_names)) bullets.push(...item.bonus_action_names.map((entry) => `Bonus Action: ${entry}`));
                    if (Array.isArray(item.reaction_names)) bullets.push(...item.reaction_names.map((entry) => `Reaction: ${entry}`));
                    if (Array.isArray(item.legendary_action_names)) bullets.push(...item.legendary_action_names.map((entry) => `Legendary Action: ${entry}`));
                    if (Array.isArray(item.lair_action_names)) bullets.push(...item.lair_action_names.map((entry) => `Lair Action: ${entry}`));

                    return `
                        <article class="library-card">
                            <h3>${escapeHtml(item.name)}</h3>
                            <p>${escapeHtml(item.summary || 'Rules entry.')}</p>
                            ${tags.length ? `<div class="chip-list">${tags.map((tag) => `<span class="chip">${escapeHtml(tag)}</span>`).join('')}</div>` : ''}
                            ${meta.length ? `<div class="entry-meta">${meta.map((entry) => escapeHtml(entry)).join(' | ')}</div>` : ''}
                            ${bullets.length ? `<ul class="entry-list">${bullets.map((entry) => `<li>${escapeHtml(entry)}</li>`).join('')}</ul>` : ''}
                        </article>
                    `;
                }).join('');
            }

            function populateSubclassOptions(selectedClass, selectedSubclass = '') {
                const subclasses = configurator.class_details?.[selectedClass]?.subclasses ?? [];
                const options = ['<option value="">Choose a subclass</option>'];

                subclasses.forEach((subclass) => {
                    const isSelected = selectedSubclass === subclass ? ' selected' : '';
                    options.push(`<option value="${subclass}"${isSelected}>${subclass}</option>`);
                });

                if (! selectedClass) {
                    subclassSelect.innerHTML = '<option value="">Choose a class first</option>';
                    return;
                }

                subclassSelect.innerHTML = options.join('');
            }

            function renderSelectionReference() {
                updateAdaptivePlaceholders();

                const nameValue = nameInput.value.trim();
                const classValue = classSelect.value;
                const subclassValue = subclassSelect.value;
                const speciesValue = speciesSelect.value;
                const backgroundValue = backgroundSelect.value;
                const alignmentValue = alignmentSelect.value;
                const originFeatValue = originFeatSelect.value;
                const languageValues = languageInputs.filter((input) => input.checked).map((input) => input.value);
                const levelValue = levelInput.value.trim();
                const personalityTraitsValue = personalityTraitsInput.value.trim();
                const idealsValue = idealsInput.value.trim();
                const bondsValue = bondsInput.value.trim();
                const flawsValue = flawsInput.value.trim();
                const appearanceValues = {
                    age: ageInput.value.trim(),
                    height: heightInput.value.trim(),
                    weight: weightInput.value.trim(),
                    eyes: eyesInput.value.trim(),
                    hair: hairInput.value.trim(),
                    skin: skinInput.value.trim(),
                };

                const classDetail = configurator.class_details?.[classValue];
                const speciesDetail = configurator.species_details?.[speciesValue];
                const backgroundDetail = configurator.background_details?.[backgroundValue];
                const alignmentDetail = configurator.alignment_details?.[alignmentValue];
                const alignmentRoleplay = configurator.alignment_roleplay?.[alignmentValue];
                const originFeatDetail = configurator.origin_feat_details?.[originFeatValue];
                const focusAbilities = Array.isArray(classDetail?.primary_focus) ? classDetail.primary_focus : [];
                const languageDetails = languageValues.map((language) => ({
                    name: language,
                    summary: configurator.language_details?.[language] || '',
                }));
                const appearanceCues = currentAppearanceCues();
                const statScores = statFields.map((field) => ({
                    field,
                    label: abilityFieldLabel(field),
                    score: Number(document.getElementById(field).value),
                })).filter((entry) => Number.isFinite(entry.score) && entry.score > 0);
                const sortedHigh = [...statScores].sort((a, b) => b.score - a.score);
                const sortedLow = [...statScores].sort((a, b) => a.score - b.score);
                const highestStat = sortedHigh[0]?.field || null;
                const lowestStat = sortedLow[0]?.field || null;
                const coreChecklist = [
                    classValue ? `Class ready: ${classValue}` : 'Class still needed',
                    levelValue ? `Level ready: ${levelValue}` : 'Level still needed',
                    subclassValue ? `Subclass ready: ${subclassValue}` : 'Subclass still needed',
                    nameValue ? `Name ready: ${nameValue}` : 'Name still needed',
                    backgroundValue ? `Background ready: ${backgroundValue}` : 'Background still needed',
                    speciesValue ? `Species ready: ${speciesValue}` : 'Species still needed',
                    originFeatValue ? `Origin feat ready: ${originFeatValue}` : 'Origin feat still needed',
                    languageValues.length ? `Languages ready: ${languageValues.join(', ')}` : 'Languages still needed',
                    statScores.length === statFields.length ? 'Ability scores ready' : `Ability scores set: ${statScores.length}/${statFields.length}`,
                    alignmentValue ? `Alignment ready: ${alignmentValue}` : 'Alignment still optional',
                ];
                const missingCore = [
                    ! classValue ? 'class' : '',
                    ! levelValue ? 'level' : '',
                    ! subclassValue ? 'subclass' : '',
                    ! nameValue ? 'name' : '',
                    ! backgroundValue ? 'background' : '',
                    ! speciesValue ? 'species' : '',
                    ! originFeatValue ? 'origin feat' : '',
                    ! languageValues.length ? 'languages' : '',
                    statScores.length !== statFields.length ? 'ability scores' : '',
                ].filter(Boolean);

                document.getElementById('selected-build-title').textContent = nameValue
                    ? `${nameValue}${classValue ? `, ${classValue}` : ''}${subclassValue ? ` (${subclassValue})` : ''}`
                    : 'Start the build';
                document.getElementById('selected-build-summary').textContent = missingCore.length
                    ? `Still to choose: ${missingCore.join(', ')}.`
                    : 'Core sheet is complete. You can still add alignment, roleplay, appearance, and notes.';
                document.getElementById('selected-build-focus').textContent = focusAbilities.length
                    ? `Step focus: class first, origin next, then scores. ${classValue} usually leans on ${focusAbilities.join(', ')}.${levelValue ? ` Level ${levelValue} is selected.` : ''}`
                    : `${levelValue ? `Level ${levelValue} is selected.` : 'Pick a class and level to see focus guidance.'}`;
                document.getElementById('selected-build-checklist').innerHTML = coreChecklist.map((entry) => `<li>${escapeHtml(entry)}</li>`).join('');

                document.getElementById('selected-stats-title').textContent = statScores.length ? 'Ability score guide' : 'Roll or enter scores';
                document.getElementById('selected-stats-summary').textContent = statScores.length
                    ? `Highest score: ${abilityFieldLabel(highestStat)}. Lowest score: ${abilityFieldLabel(lowestStat)}.`
                    : 'Roll or enter scores to see modifiers, class-fit advice, and where the build is strongest.';
                document.getElementById('selected-stats-list').innerHTML = statScores.length
                    ? statFields.map((field) => {
                        const label = abilityFieldLabel(field);
                        const score = Number(document.getElementById(field).value);
                        if (! Number.isFinite(score) || score <= 0) {
                            return `<li><strong>${label}</strong>: waiting for a score.</li>`;
                        }

                        const tags = [];
                        if (focusAbilities.includes(label)) tags.push('class focus');
                        if (field === highestStat) tags.push('highest');
                        if (field === lowestStat) tags.push('lowest');

                        return `<li><strong>${label} ${score} (${formatModifier(abilityModifier(score))})</strong>: ${escapeHtml(configurator.ability_details?.[label] || 'Score notes appear here once the value is set.')}${tags.length ? ` <span class="tiny">(${escapeHtml(tags.join(', '))})</span>` : ''}</li>`;
                    }).join('')
                    : '<li>Primary class focus and ability modifiers will appear here.</li>';

                document.getElementById('selected-class-title').textContent = classValue
                    ? `${classValue}${subclassValue ? ` / ${subclassValue}` : ''}`
                    : 'Choose a class';
                document.getElementById('selected-class-summary').textContent = classDetail?.summary || 'Class notes appear here once you make a choice.';
                document.getElementById('selected-class-focus').textContent = Array.isArray(classDetail?.primary_focus) && classDetail.primary_focus.length
                    ? `Primary focus: ${classDetail.primary_focus.join(', ')}${subclassValue ? ` | Selected subclass: ${subclassValue}` : ''}`
                    : 'Primary focus and playstyle notes will appear here.';
                document.getElementById('selected-subclasses').innerHTML = (classDetail?.subclasses || []).length
                    ? (classDetail.subclasses || []).map((subclass) => `<li>${escapeHtml(subclass)}${subclassValue === subclass ? ' <span class="tiny">(selected)</span>' : ''}</li>`).join('')
                    : '<li>Subclass options will appear after you choose a class.</li>';

                document.getElementById('selected-species-title').textContent = speciesValue || 'Choose a species';
                document.getElementById('selected-species-summary').textContent = speciesDetail?.summary || 'Species notes appear here once you make a choice.';
                document.getElementById('selected-species-traits').innerHTML = speciesDetail
                    ? [
                        speciesDetail.size ? `<li>Size: ${speciesDetail.size}</li>` : '',
                        speciesDetail.speed ? `<li>Speed: ${speciesDetail.speed}</li>` : '',
                        ...(speciesDetail.traits || []).map((trait) => `<li>Trait: ${trait}</li>`),
                    ].filter(Boolean).join('')
                    : '<li>Size, speed, and species traits will appear here.</li>';

                document.getElementById('selected-background-title').textContent = backgroundValue || 'Choose a background';
                document.getElementById('selected-background-summary').textContent = backgroundDetail?.summary || 'Background notes appear here once you make a choice.';
                document.getElementById('selected-background-theme').textContent = backgroundDetail?.theme
                    ? `Theme: ${backgroundDetail.theme}`
                    : 'Background themes and roleplay hooks will appear here.';

                document.getElementById('selected-alignment-title').textContent = alignmentValue || 'Choose an alignment';
                document.getElementById('selected-alignment-summary').textContent = alignmentDetail || 'Your selected alignment summary will appear here.';

                document.getElementById('selected-origin-feat-title').textContent = originFeatValue || 'Choose an origin feat';
                document.getElementById('selected-origin-feat-summary').textContent = originFeatDetail || 'Feat notes appear here once you make a choice.';

                document.getElementById('selected-languages-title').textContent = languageValues.length ? `Languages (${languageValues.length})` : 'Choose languages';
                document.getElementById('selected-language-list').innerHTML = languageDetails.length
                    ? languageDetails.map((language) => `<li><strong>${language.name}</strong>${language.summary ? `: ${language.summary}` : ''}</li>`).join('')
                    : '<li>Your selected language summaries will appear here.</li>';

                document.getElementById('selected-roleplay-title').textContent = alignmentValue ? `${alignmentValue} roleplay starter` : 'Beginner roleplay help';
                document.getElementById('selected-roleplay-summary').textContent = alignmentRoleplay?.play_well
                    || 'Pick one alignment and a few short personality anchors. You do not need to write a novel.';
                document.getElementById('selected-roleplay-list').innerHTML = [
                    alignmentRoleplay?.watch_out ? `<li><strong>Watch out:</strong> ${alignmentRoleplay.watch_out}</li>` : '',
                    personalityTraitsValue ? `<li><strong>Current trait:</strong> ${escapeHtml(personalityTraitsValue)}</li>` : `<li><strong>Trait idea:</strong> ${escapeHtml(alignmentRoleplay?.starter_trait || configurator.roleplay_field_help?.personality_traits || '')}</li>`,
                    idealsValue ? `<li><strong>Current ideal:</strong> ${escapeHtml(idealsValue)}</li>` : `<li><strong>Ideal idea:</strong> ${escapeHtml(alignmentRoleplay?.starter_ideal || configurator.roleplay_field_help?.ideals || '')}</li>`,
                    bondsValue ? `<li><strong>Current bond:</strong> ${escapeHtml(bondsValue)}</li>` : `<li><strong>Bond idea:</strong> ${escapeHtml(alignmentRoleplay?.starter_bond || configurator.roleplay_field_help?.bonds || '')}</li>`,
                    flawsValue ? `<li><strong>Current flaw:</strong> ${escapeHtml(flawsValue)}</li>` : `<li><strong>Flaw idea:</strong> ${escapeHtml(alignmentRoleplay?.starter_flaw || configurator.roleplay_field_help?.flaws || '')}</li>`,
                ].filter(Boolean).join('');

                document.getElementById('selected-appearance-title').textContent = (appearanceValues.eyes || appearanceValues.hair || appearanceValues.skin)
                    ? 'Current look'
                    : 'Appearance help';
                document.getElementById('selected-appearance-summary').textContent = 'A few visual anchors are enough: age, height, eyes, hair, and one memorable detail.';
                document.getElementById('selected-appearance-list').innerHTML = [
                    appearanceValues.age ? `<li><strong>Age:</strong> ${escapeHtml(appearanceValues.age)}</li>` : `<li><strong>Age:</strong> ${escapeHtml(configurator.appearance_field_help?.age || '')}</li>`,
                    appearanceValues.height ? `<li><strong>Height:</strong> ${escapeHtml(appearanceValues.height)}</li>` : `<li><strong>Height:</strong> ${escapeHtml(configurator.appearance_field_help?.height || '')}</li>`,
                    appearanceValues.weight ? `<li><strong>Weight:</strong> ${escapeHtml(appearanceValues.weight)}</li>` : `<li><strong>Weight:</strong> ${escapeHtml(configurator.appearance_field_help?.weight || '')}</li>`,
                    appearanceValues.eyes ? `<li><strong>Eyes:</strong> ${escapeHtml(appearanceValues.eyes)}</li>` : `<li><strong>Eyes:</strong> ${escapeHtml(configurator.appearance_field_help?.eyes || '')}</li>`,
                    appearanceValues.hair ? `<li><strong>Hair:</strong> ${escapeHtml(appearanceValues.hair)}</li>` : `<li><strong>Hair:</strong> ${escapeHtml(configurator.appearance_field_help?.hair || '')}</li>`,
                    appearanceValues.skin ? `<li><strong>Skin:</strong> ${escapeHtml(appearanceValues.skin)}</li>` : `<li><strong>Skin:</strong> ${escapeHtml(configurator.appearance_field_help?.skin || '')}</li>`,
                    ...appearanceCues.map((cue) => `<li><strong>Cue:</strong> ${escapeHtml(cue)}</li>`),
                ].join('');

                syncSelectTitle(classSelect, 'class');
                syncSelectTitle(subclassSelect, 'subclass');
                syncSelectTitle(backgroundSelect, 'background');
                syncSelectTitle(speciesSelect, 'species');
                syncSelectTitle(originFeatSelect, 'origin_feat');
                syncSelectTitle(alignmentSelect, 'alignment');
            }

            function randomInt(min, max) {
                return Math.floor(Math.random() * (max - min + 1)) + min;
            }

            function randomChoice(items) {
                return items.length ? items[randomInt(0, items.length - 1)] : null;
            }

            function randomUniqueChoices(items, count) {
                const pool = [...items];
                const picks = [];

                while (pool.length && picks.length < count) {
                    const index = randomInt(0, pool.length - 1);
                    picks.push(pool.splice(index, 1)[0]);
                }

                return picks;
            }

            function splitSuggestionPool(value) {
                return String(value ?? '')
                    .replaceAll('...', '')
                    .split(',')
                    .map((entry) => entry.replace(/^or\s+/i, '').trim())
                    .filter(Boolean);
            }

            function randomFromSuggestion(value, fallback = '') {
                const pool = splitSuggestionPool(value);
                return randomChoice(pool) || fallback;
            }

            function localAbilityScore() {
                const rolls = Array.from({ length: 4 }, () => randomInt(1, 6)).sort((a, b) => a - b);
                rolls.shift();
                return rolls.reduce((total, roll) => total + roll, 0);
            }

            function randomLanguagesSelection() {
                const allLanguages = Object.keys(configurator.language_details || {});
                const nonCommon = allLanguages.filter((language) => language !== 'Common');
                const picks = allLanguages.includes('Common') ? ['Common'] : [];
                const extras = randomUniqueChoices(nonCommon, Math.min(nonCommon.length, randomInt(1, 2)));
                const selection = [...picks, ...extras];

                return selection.length ? selection : randomUniqueChoices(allLanguages, 1);
            }

            function randomCharacterName(species) {
                const speciesProfile = configurator.form_placeholder_profiles?.species?.[species] || {};
                return randomFromSuggestion(speciesProfile.name, `${species} Wanderer`);
            }

            function randomAppearanceProfile(species) {
                const defaultProfile = configurator.form_placeholder_profiles?.default || {};
                const speciesProfile = configurator.form_placeholder_profiles?.species?.[species] || {};
                const mergedProfile = { ...defaultProfile, ...speciesProfile };

                return {
                    age: randomFromSuggestion(mergedProfile.age, '25'),
                    height: randomFromSuggestion(mergedProfile.height, '173 cm'),
                    weight: randomFromSuggestion(mergedProfile.weight, '72 kg'),
                    eyes: randomFromSuggestion(mergedProfile.eyes, 'Gray'),
                    hair: randomFromSuggestion(mergedProfile.hair, 'Dark braid'),
                    skin: randomFromSuggestion(mergedProfile.skin, 'Weathered'),
                };
            }

            function fillRandomCharacter() {
                const classOptions = Object.keys(configurator.class_details || {});
                const speciesOptions = Object.keys(configurator.species_details || {});
                const backgroundOptions = Object.keys(configurator.background_details || {});
                const alignmentOptions = Object.keys(configurator.alignment_details || {});
                const originFeatOptions = Object.keys(configurator.origin_feat_details || {});

                const classValue = randomChoice(classOptions);
                const speciesValue = randomChoice(speciesOptions);
                const backgroundValue = randomChoice(backgroundOptions);
                const alignmentValue = randomChoice(alignmentOptions);
                const originFeatValue = randomChoice(originFeatOptions);
                const subclassValue = randomChoice(configurator.class_details?.[classValue]?.subclasses || []);
                const languagesValue = randomLanguagesSelection();
                const roleplayProfile = configurator.alignment_roleplay?.[alignmentValue] || {};
                const appearance = randomAppearanceProfile(speciesValue);
                const levelValue = randomInt(1, 20);
                const generatedStats = Object.fromEntries(statFields.map((field) => [field, localAbilityScore()]));
                const nameValue = randomCharacterName(speciesValue);
                const backgroundTheme = configurator.background_details?.[backgroundValue]?.theme || 'adventuring trouble';

                clearNotice(formNotice);
                form.reset();

                classSelect.value = classValue || '';
                levelInput.value = levelValue;
                populateSubclassOptions(classValue || '');
                subclassSelect.value = subclassValue || '';
                backgroundSelect.value = backgroundValue || '';
                speciesSelect.value = speciesValue || '';
                originFeatSelect.value = originFeatValue || '';
                alignmentSelect.value = alignmentValue || '';
                nameInput.value = nameValue || '';

                languageInputs.forEach((input) => {
                    input.checked = languagesValue.includes(input.value);
                });

                renderDiceStatsResult(generatedStats, { populateForm: true });

                personalityTraitsInput.value = roleplayProfile.starter_trait || personalityTraitsInput.placeholder;
                idealsInput.value = roleplayProfile.starter_ideal || idealsInput.placeholder;
                bondsInput.value = roleplayProfile.starter_bond || bondsInput.placeholder;
                flawsInput.value = roleplayProfile.starter_flaw || flawsInput.placeholder;
                ageInput.value = appearance.age;
                heightInput.value = appearance.height;
                weightInput.value = appearance.weight;
                eyesInput.value = appearance.eyes;
                hairInput.value = appearance.hair;
                skinInput.value = appearance.skin;
                notesInput.value = `${nameValue} is a level ${levelValue} ${subclassValue} ${classValue} with a ${backgroundValue.toLowerCase()} background. Speaks ${languagesValue.join(', ')}. Ask the DM how their ${backgroundTheme.toLowerCase()} first tied them to the party.`;

                renderSelectionReference();
                notice(formNotice, 'Random character generated. Review anything you want, then save the sheet.', 'success');
            }

            async function loadCharacterCount() {
                try {
                    const response = await fetch('/api/characters', { headers: { Accept: 'application/json' } });
                    if (! response.ok) throw new Error();
                    const characters = await response.json();
                    countEl.textContent = Array.isArray(characters) ? characters.length : '0';
                } catch {
                    countEl.textContent = '-';
                }
            }

            async function rollStats(options = {}) {
                const { populateForm = true } = options;

                try {
                    const response = await fetch('/api/roll-stats', { method: 'POST', headers: { Accept: 'application/json' } });
                    if (! response.ok) throw new Error();
                    const stats = await response.json();
                    renderDiceStatsResult(stats, { populateForm });
                    clearNotice(formNotice);
                    clearNotice(diceNotice);
                    if (populateForm) renderSelectionReference();
                } catch {
                    notice(formNotice, 'Rolling stats failed. Try again in a moment.', 'error');
                    if (! populateForm) notice(diceNotice, 'Rolling stats failed. Try again in a moment.', 'error');
                }
            }

            async function rollDiceExpression(expression, mode = '') {
                try {
                    clearNotice(diceNotice);
                    const response = await fetch('/api/roll-dice', {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            expression,
                            mode: mode || null,
                        }),
                    });

                    const data = await response.json();
                    if (! response.ok) {
                        throw new Error(data.message || 'The dice roll failed.');
                    }

                    renderDiceExpressionResult(data);
                } catch (error) {
                    notice(diceNotice, error.message || 'The dice roll failed.', 'error');
                }
            }

            function resetForm() {
                form.reset();
                document.getElementById('level').value = 1;
                renderPreview(null);
                populateSubclassOptions('');
                renderSelectionReference();
                clearNotice(formNotice);
            }

            function resetDiceTray() {
                diceForm.reset();
                renderDiceResultCard('Ready to roll', ['Pick any die, use a custom expression, or roll full ability scores from here.']);
                clearNotice(diceNotice);
                setLatestRoll('Ready');
            }

            function payload() {
                const data = Object.fromEntries(new FormData(form).entries());
                delete data['languages[]'];
                optionalTextFields.forEach((field) => {
                    data[field] = typeof data[field] === 'string' && data[field].trim() ? data[field].trim() : null;
                });
                data.languages = languageInputs.filter((input) => input.checked).map((input) => input.value);
                data.level = Number(data.level);
                statFields.forEach((field) => data[field] = Number(data[field]));
                return data;
            }

            async function createCharacter(event) {
                event.preventDefault();
                clearNotice(formNotice);

                if (! languageInputs.some((input) => input.checked)) {
                    notice(formNotice, 'Choose at least one language before saving the character.', 'error');
                    return;
                }

                try {
                    const response = await fetch('/api/characters', {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload()),
                    });

                    const data = await response.json();
                    if (! response.ok) {
                        const message = data.errors ? Object.values(data.errors)[0][0] : 'The character could not be saved.';
                        throw new Error(message);
                    }

                    notice(formNotice, 'Character saved successfully.', 'success');
                    resetForm();
                    await loadCharacterCount();
                } catch (error) {
                    notice(formNotice, error.message || 'The character could not be saved.', 'error');
                }
            }

            async function sendWizardMessage(message, options = {}) {
                const { echoUser = true, reset = false } = options;

                if (reset) {
                    wizardState = {};
                    wizardLog.innerHTML = '';
                    renderWizardActions([]);
                    renderWizardSummary(null);
                }

                if (message && echoUser) {
                    appendWizardMessage('user', message);
                }

                try {
                    clearNotice(wizardNotice);

                    const response = await fetch('/api/rules-wizard/message', {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message,
                            state: wizardState,
                        }),
                    });

                    const data = await response.json();
                    if (! response.ok) {
                        const message = data.errors ? Object.values(data.errors)[0][0] : 'The rules wizard could not respond.';
                        throw new Error(message);
                    }

                    wizardState = data.state ?? {};
                    appendWizardMessage('bot', data.reply ?? 'No response.');
                    renderWizardActions(data.quick_actions ?? []);
                    renderWizardSummary(data.snapshot ?? null);

                    if (/^(roll|roll initiative|roll death save)/i.test(message || '')) {
                        setLatestRoll((data.reply ?? 'Rolled').split('\n')[0]);
                    }
                } catch (error) {
                    notice(wizardNotice, error.message || 'The rules wizard could not respond.', 'error');
                }
            }

            document.getElementById('roll-btn').addEventListener('click', rollStats);
            document.getElementById('random-character-btn').addEventListener('click', fillRandomCharacter);
            document.getElementById('clear-btn').addEventListener('click', resetForm);
            document.getElementById('dice-roll-stats').addEventListener('click', () => rollStats({ populateForm: false }));
            document.getElementById('dice-clear').addEventListener('click', resetDiceTray);
            document.getElementById('wizard-reset').addEventListener('click', () => sendWizardMessage('', { echoUser: false, reset: true }));
            tryAskingSearch.addEventListener('input', filterTryAsking);
            tryAskingContainer.addEventListener('click', (event) => {
                const button = event.target.closest('[data-try-command]');
                if (! button) return;
                sendWizardMessage(button.dataset.tryCommand);
            });
            form.addEventListener('submit', createCharacter);
            diceForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const expression = diceExpressionInput.value.trim();
                if (! expression) return;
                rollDiceExpression(expression, diceModeSelect.value);
            });
            wizardForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const message = wizardInput.value.trim();
                if (! message) return;
                wizardInput.value = '';
                sendWizardMessage(message);
            });
            compendiumSectionSelect.addEventListener('change', renderCompendium);
            compendiumSearchInput.addEventListener('input', renderCompendium);
            diceButtons.addEventListener('click', (event) => {
                const button = event.target.closest('[data-dice-expression]');
                if (! button) return;
                rollDiceExpression(button.dataset.diceExpression, button.dataset.diceMode || '');
            });
            wireChoiceHelp(classSelect, 'class', step1ChoiceHelp, 'Class');
            wireChoiceHelp(subclassSelect, 'subclass', step1ChoiceHelp, 'Subclass');
            wireChoiceHelp(backgroundSelect, 'background', step2ChoiceHelp, 'Background');
            wireChoiceHelp(speciesSelect, 'species', step2ChoiceHelp, 'Species');
            wireChoiceHelp(originFeatSelect, 'origin_feat', step2ChoiceHelp, 'Origin Feat');
            wireChoiceHelp(alignmentSelect, 'alignment', step4ChoiceHelp, 'Alignment');

            nameInput.addEventListener('input', renderSelectionReference);
            classSelect.addEventListener('change', () => {
                populateSubclassOptions(classSelect.value);
                renderSelectionReference();
            });
            subclassSelect.addEventListener('change', renderSelectionReference);
            speciesSelect.addEventListener('change', renderSelectionReference);
            backgroundSelect.addEventListener('change', renderSelectionReference);
            alignmentSelect.addEventListener('change', renderSelectionReference);
            originFeatSelect.addEventListener('change', renderSelectionReference);
            languageInputs.forEach((input) => input.addEventListener('change', renderSelectionReference));
            languageInputs.forEach((input) => {
                const chip = input.closest('.check-chip');
                const show = () => {
                    const description = formChoiceDescription('language', input.value);
                    showHoverHelp(step2ChoiceHelp, `Language: ${input.value}`, description || 'Language reference note.');
                };

                chip?.addEventListener('mouseenter', show);
                chip?.addEventListener('mouseleave', () => hideHoverHelp(step2ChoiceHelp));
                chip?.addEventListener('focusin', show);
                chip?.addEventListener('focusout', () => hideHoverHelp(step2ChoiceHelp));
                input.title = formChoiceDescription('language', input.value) || '';
            });
            levelInput.addEventListener('input', renderSelectionReference);
            [personalityTraitsInput, idealsInput, bondsInput, flawsInput, ageInput, heightInput, weightInput, eyesInput, hairInput, skinInput].forEach((input) => {
                input.addEventListener('input', renderSelectionReference);
            });
            statFields.forEach((field) => {
                document.getElementById(field).addEventListener('input', renderSelectionReference);
            });

            wizardActions.addEventListener('click', (event) => {
                const button = event.target.closest('[data-wizard-action]');
                if (button) sendWizardMessage(button.dataset.wizardAction);
            });
            wizardDiceButtons.forEach((button) => {
                button.addEventListener('click', () => sendWizardMessage(button.dataset.wizardCommand));
            });

            populateSubclassOptions('');
            filterTryAsking();
            resetDiceTray();
            renderSelectionReference();
            if (compendiumSections.length) {
                compendiumSectionSelect.value = compendiumSections[0].key;
            }
            renderCompendium();
            loadCharacterCount();
            sendWizardMessage('', { echoUser: false });
        </script>
    </body>
</html>
