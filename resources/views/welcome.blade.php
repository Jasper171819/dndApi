{{-- Developer context: This Blade template renders the main builder page; PHP prepares the config and compendium payloads, and the browser-side script below drives the interactive builder, wizard, dice tray, autosave, and library UI. --}}
{{-- Clear explanation: This file is the main page people use to build characters, talk to the wizard, roll dice, and browse the rules library. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Adventurer's Ledger</title>
        <style>
            :root{--bg:#130f0d;--panel:#221915;--soft:#31231d;--line:#4e372a;--text:#f7ead8;--muted:#d8bea0;--accent:#d58345;--accent2:#efba70}
            *{box-sizing:border-box} html{scroll-behavior:smooth;scroll-padding-top:7.5rem}
            body{position:relative;min-height:100vh;margin:0;font-family:"Trebuchet MS","Segoe UI",sans-serif;color:var(--text);background:#100c0b;overflow-x:hidden}
            body::before{content:"";position:fixed;inset:0;z-index:-2;background:radial-gradient(circle at top left,rgba(213,131,69,.22),transparent 25%),linear-gradient(160deg,#100c0b,#1b1411 50%,#0e0b0a)}
            body::after{content:"";position:fixed;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(0,0,0,.06),rgba(0,0,0,.18))}
            button,input,textarea,select{font:inherit} a{text-decoration:none;color:inherit}
            .wrap{position:relative;z-index:1;width:min(1320px,calc(100% - 2rem));margin:0 auto;padding:1rem 0 4rem}
            .workspace{display:grid;grid-template-columns:minmax(165px,190px) minmax(0,1fr) minmax(215px,240px);gap:1rem;align-items:start}
            .topbar,.card,.panel,.char,.stat,.notice,.mini{border:1px solid var(--line);border-radius:24px;background:rgba(34,25,21,.9);box-shadow:0 20px 50px rgba(0,0,0,.28)}
            .topbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:1rem 1.2rem;margin-top:1rem;border-radius:999px;background:rgba(19,15,13,.84);backdrop-filter:blur(10px)}
            .brand{display:flex;align-items:center;gap:.85rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
            .mark{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#25170f;font-weight:900}
            .nav{display:flex;flex-wrap:wrap;gap:.6rem}
            .nav a,.btn,.btn-soft{padding:.8rem 1rem;border-radius:999px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);cursor:pointer;transition:.18s ease}
            .btn:hover,.btn-soft:hover,.nav a:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
            .nav a.active{border-color:#8b623f;color:var(--text);background:rgba(255,255,255,.08)}
            .btn{background:linear-gradient(135deg,var(--accent),var(--accent2));border-color:transparent;color:#29180f;font-weight:700}
            main#top{display:grid;gap:1.4rem;grid-column:2;grid-row:1}
            main#top > section{margin-top:0}
            .page-rail{position:sticky;top:6.5rem;align-self:start;margin-top:2rem;grid-column:1;grid-row:1}
            .page-rail-card{padding:1rem}
            .page-rail-links{display:grid;gap:.6rem;margin-top:.8rem}
            .page-rail-links a{display:block;padding:.75rem .9rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.03);color:var(--muted);transition:.18s ease}
            .page-rail-links a:hover{transform:translateY(-1px);border-color:#8b623f;color:var(--text)}
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
            .preview-rail{position:sticky;top:6.5rem;align-self:start;margin-top:2rem;grid-column:3;grid-row:1}
            .preview-panel p{margin:.45rem 0 0}
            .preview-stats{grid-template-columns:repeat(2,minmax(0,1fr));margin-top:1rem}
            .preview-stat{padding:.85rem .75rem}
            .preview-stat .value{font-size:1.35rem}
            .preview-mod{display:block;margin-top:.25rem;font-size:.84rem;color:var(--accent2);letter-spacing:.08em}
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
            .skill-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.7rem}
            .check-chip{display:block;color:inherit;font-size:inherit}
            .check-chip-input{position:absolute;opacity:0;pointer-events:none}
            .check-chip-label{display:block;padding:.9rem 1rem;border-radius:16px;border:1px solid var(--line);background:rgba(255,255,255,.04);color:var(--text);cursor:pointer;transition:.18s ease}
            .check-chip:hover .check-chip-label{border-color:#8b623f;transform:translateY(-1px)}
            .check-chip-input:focus-visible + .check-chip-label{outline:2px solid rgba(239,186,112,.6);outline-offset:2px}
            .check-chip-input:checked + .check-chip-label{border-color:#476b3f;background:linear-gradient(135deg,rgba(43,76,49,.94),rgba(70,108,64,.95));color:#f4fff1;box-shadow:0 10px 24px rgba(28,52,34,.32)}
            .check-chip-input:disabled + .check-chip-label{opacity:.48;cursor:not-allowed;transform:none;box-shadow:none;filter:saturate(.45)}
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
            .hover-tooltip{position:fixed;top:0;left:0;z-index:40;width:min(320px,calc(100vw - 1rem));padding:.95rem 1rem;border-radius:18px;border:1px solid rgba(139,98,63,.78);background:rgba(28,20,16,.96);box-shadow:0 24px 60px rgba(0,0,0,.4);color:var(--muted);backdrop-filter:blur(10px);pointer-events:none;opacity:0;transform:translateY(6px);transition:opacity .12s ease,transform .12s ease}
            .hover-tooltip.show{opacity:1;transform:translateY(0)}
            .hover-tooltip strong{display:block;margin-bottom:.35rem;color:var(--accent2);font-size:.82rem;letter-spacing:.08em;text-transform:uppercase}
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
            #snapshot{order:2}
            #forge{order:3}
            #dice{order:4}
            #wizard{order:5}
            #library{order:6}
            @media (max-width:1120px){.workspace{grid-template-columns:160px minmax(0,1fr) 205px}}
            @media (max-width:920px){.workspace,.hero,.grid,.library-grid,.library-toolbar,.summary-grid,.quick{grid-template-columns:1fr}.topbar{position:static;border-radius:28px;align-items:stretch}.nav{justify-content:center}.page-rail,.preview-rail{position:static;margin-top:0;grid-column:auto;grid-row:auto}.page-rail-links{grid-template-columns:repeat(2,minmax(0,1fr))}}
            @media (max-width:720px){.section-grid,.stats,.quick,.check-grid,.skill-grid{grid-template-columns:1fr}.topbar{border-radius:26px;padding:1rem}.brand{justify-content:center}.nav a{flex:1 1 calc(50% - .6rem);text-align:center}.head{flex-direction:column;align-items:start}}
            @media (max-width:720px){.dice-buttons,.dice-form{grid-template-columns:1fr}}
            @media (max-width:640px){.wrap{width:min(100% - 1rem,100%)}.topbar,.card,.panel{padding:1.05rem}h1{max-width:100%}.library-results{column-width:auto;columns:1}.hover-tooltip{width:min(280px,calc(100vw - 1rem))}}
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
                {{-- Developer context: This rail keeps page-local navigation separate from the main content flow. --}}
                {{-- Clear explanation: This side block is the quick menu for jumping around the page. --}}
                <aside class="page-rail">
                    <section class="panel page-rail-card">
                        <span class="eyebrow">On This Page</span>
                        <nav class="page-rail-links">
                            <a href="#overview">Overview</a>
                            <a href="#snapshot">Snapshot</a>
                            <a href="#forge">Builder</a>
                            <a href="#build-guide">Build Guide</a>
                            <a href="#dice">Dice</a>
                            <a href="#wizard">Wizard</a>
                            <a href="#library">Library</a>
                        </nav>
                    </section>
                </aside>

                {{-- Developer context: This rail keeps the live ability summary visible without mixing it into the main builder stack. --}}
                {{-- Clear explanation: This side block keeps the ability tracker on screen while someone builds. --}}
                <aside class="preview-rail">
                    <section class="panel preview-panel">
                        <span class="eyebrow">Ability Tracker</span>
                        <h2>Scores and modifiers</h2>
                        <p class="tiny">Keep the six abilities in view while you build. Hover a score here or in Step 3 to see the save bonus, related skills, and any saved proficiency or expertise tied to that ability.</p>
                        <div class="stats preview-stats" id="preview">
                            <div class="stat preview-stat" tabindex="0" data-preview-field="strength"><span class="label">STR</span><span class="value">--</span><span class="preview-mod">Mod --</span></div>
                            <div class="stat preview-stat" tabindex="0" data-preview-field="dexterity"><span class="label">DEX</span><span class="value">--</span><span class="preview-mod">Mod --</span></div>
                            <div class="stat preview-stat" tabindex="0" data-preview-field="constitution"><span class="label">CON</span><span class="value">--</span><span class="preview-mod">Mod --</span></div>
                            <div class="stat preview-stat" tabindex="0" data-preview-field="intelligence"><span class="label">INT</span><span class="value">--</span><span class="preview-mod">Mod --</span></div>
                            <div class="stat preview-stat" tabindex="0" data-preview-field="wisdom"><span class="label">WIS</span><span class="value">--</span><span class="preview-mod">Mod --</span></div>
                            <div class="stat preview-stat" tabindex="0" data-preview-field="charisma"><span class="label">CHA</span><span class="value">--</span><span class="preview-mod">Mod --</span></div>
                        </div>
                    </section>
                </aside>

                <main id="top">
                {{-- Developer context: This overview section introduces the page and points people toward the main feature paths. --}}
                {{-- Clear explanation: This block is the page introduction and the fastest way into the builder. --}}
                <section class="hero" id="overview">
                    <article class="card">
                        <span class="eyebrow">Character Builder</span>
                        <h1>Build a character and keep the whole sheet in one place.</h1>
                        <p>Create a full character sheet, roll stats, save your roster, and browse the rules without bouncing between tools.</p>
                        <p class="tiny">The builder, wizard, and library stay on the verified official catalog. Custom material now lives on the separate Homebrew page.</p>
                        <p class="tiny">Drafts on this page now autosave locally in this browser, so a reload or connection drop does not have to wipe your in-progress work.</p>
                        <div class="notice" id="draft-notice"></div>
                        <div class="hero-actions">
                            <a class="btn" href="#forge">Open builder</a>
                            <a class="btn-soft" href="{{ route('dm') }}">Open DM desk</a>
                            <a class="btn-soft" href="{{ route('roster') }}">Open roster</a>
                            <a class="btn-soft" href="{{ route('homebrew') }}">Homebrew workshop</a>
                        </div>
                    </article>
                </section>

                {{-- Developer context: This snapshot section surfaces the most useful status numbers without forcing a full page scan. --}}
                {{-- Clear explanation: This block shows the quick status overview for the current page. --}}
                <section class="panel" id="snapshot">
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

                {{-- Developer context: This section keeps the wizard chat, helper actions, and summary grouped as one workflow. --}}
                {{-- Clear explanation: This block is the guided chat area that walks through the character and rules. --}}
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

                        <form class="wizard-form" id="wizard-form">
                            <input id="wizard-input" type="text" maxlength="500" placeholder="Type a command like new character, roll d20+5, show summary, or level up">
                            <button class="btn" type="submit">Send</button>
                        </form>
                    </div>

                    <div class="panel">
                        <span class="eyebrow">Wizard Support</span>
                        <div class="rule-block">
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

                {{-- Developer context: This section contains the full structured builder form and the supporting guide that mirrors it. --}}
                {{-- Clear explanation: This block is the main character builder form. --}}
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
                                        <p>Class is the biggest gameplay choice. This step also sets level, table pacing for leveling, and the class-side training the sheet should track. Everything in this step is required except expertise.</p>
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
                                            <span>Advancement Method</span>
                                            <select id="advancement_method" name="advancement_method" required>
                                                <option value="">Choose how the table handles level-ups</option>
                                                @foreach (config('dnd.advancement_methods', []) as $advancementMethod)
                                                    <option value="{{ $advancementMethod }}">{{ $advancementMethod }}</option>
                                                @endforeach
                                            </select>
                                            <span class="tiny">This sets the table’s pacing rule for leveling, so the wizard can frame progression and roleplay with the right context.</span>
                                        </label>
                                        <label class="full">
                                            <span>Subclass</span>
                                            <select id="subclass" name="subclass" required>
                                                <option value="">Choose a class first</option>
                                            </select>
                                        </label>
                                        <label class="full">
                                            <span>Skill Proficiencies</span>
                                            <div class="skill-grid" id="skill-proficiencies">
                                                @foreach (config('dnd.skills', []) as $skill)
                                                    <label class="check-chip">
                                                        <input class="check-chip-input" type="checkbox" name="skill_proficiencies[]" value="{{ $skill }}">
                                                        <span class="check-chip-label">{{ $skill }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <span class="tiny" id="class-skill-note">Choose at least one skill the sheet should treat as proficient. The class note here will narrow the usual starting choices once you pick a class.</span>
                                        </label>
                                        <label class="full">
                                            <span>Skill Expertise</span>
                                            <div class="skill-grid" id="skill-expertise">
                                                @foreach (config('dnd.skills', []) as $skill)
                                                    <label class="check-chip">
                                                        <input class="check-chip-input" type="checkbox" name="skill_expertise[]" value="{{ $skill }}">
                                                        <span class="check-chip-label">{{ $skill }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <span class="tiny" id="expertise-note">Only mark expertise on skills that already have proficiency. If the build does not get expertise yet, leave this empty.</span>
                                        </label>
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
                                            <input id="name" name="name" required maxlength="255" placeholder="Rin, Mara, Toren...">
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
                                            <textarea id="personality_traits" name="personality_traits" maxlength="1000" placeholder="Short first-impression notes like calm, curious, dry humor..."></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.personality_traits') }}</span>
                                        </label>
                                        <label class="full">
                                            <span>Ideals</span>
                                            <textarea id="ideals" name="ideals" maxlength="1000" placeholder="What principle matters most to this character?"></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.ideals') }}</span>
                                        </label>
                                        <label class="full">
                                            <span>Goals</span>
                                            <textarea id="goals" name="goals" maxlength="1000" placeholder="What does this character want to achieve, protect, prove, or uncover next?"></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.goals') }}</span>
                                        </label>
                                        <label class="full">
                                            <span>Bonds</span>
                                            <textarea id="bonds" name="bonds" maxlength="1000" placeholder="Who or what matters enough to change their decisions?"></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.bonds') }}</span>
                                        </label>
                                        <label class="full">
                                            <span>Flaws</span>
                                            <textarea id="flaws" name="flaws" maxlength="1000" placeholder="What weakness or habit tends to cause trouble?"></textarea>
                                            <span class="tiny">{{ config('dnd.roleplay_field_help.flaws') }}</span>
                                        </label>
                                        <div class="full tiny" id="roleplay-placeholder-note">Roleplay prompts adapt to alignment, class, and background. They are examples, not limits.</div>
                                        <label><span>Age</span><input id="age" name="age" maxlength="255" placeholder="23 or 120"></label>
                                        <label><span>Height</span><input id="height" name="height" maxlength="255" placeholder="173 cm or 5ft 8in"></label>
                                        <label><span>Weight</span><input id="weight" name="weight" maxlength="255" placeholder="72 kg or 160 lb"></label>
                                        <label><span>Eyes</span><input id="eyes" name="eyes" maxlength="255" placeholder="Gray, green, amber..."></label>
                                        <label><span>Hair</span><input id="hair" name="hair" maxlength="255" placeholder="Black braid, copper curls..."></label>
                                        <label><span>Skin</span><input id="skin" name="skin" maxlength="255" placeholder="Olive, freckled, scarred..."></label>
                                        <div class="full tiny" id="appearance-placeholder-note">Appearance examples shift with species. They are lore-style examples, not hard limits.</div>
                                        <label class="full"><span>Notes</span><textarea id="notes" name="notes" maxlength="2000" placeholder="Campaign notes, hooks, gear, personality..."></textarea></label>
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

                    <div class="panel" id="build-guide">
                        <span class="eyebrow">Build Guide</span>
                        <div class="summary-grid">
                            <div class="rule-block">
                                <h3 id="selected-build-title">Start the build</h3>
                                <p id="selected-build-summary">Work top to bottom: class, level and pacing, origin, ability scores, alignment, then the rest of the sheet.</p>
                                <p id="selected-build-focus" class="tiny">This panel updates live so you can see what is already covered and what still needs attention.</p>
                                <ul id="selected-build-checklist"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-official-title">Official 2024 check</h3>
                                <p id="selected-official-summary">Warnings appear here when the current sheet moves away from the official 2024 default.</p>
                                <ul id="selected-official-list"><li>No official-rules warnings right now.</li></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-class-title">Choose a class</h3>
                                <p id="selected-class-summary">Class notes appear here once you make a choice.</p>
                                <p id="selected-class-focus" class="tiny">Primary focus and playstyle notes will appear here.</p>
                                <ul id="selected-subclasses"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-skills-title">Skill training</h3>
                                <p id="selected-skills-summary">Choose the skills the sheet should treat as proficient, then add expertise only where something on the sheet grants it.</p>
                                <ul id="selected-skills-list"></ul>
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
                                <p id="selected-roleplay-summary">Keep this light: one short line each for trait, ideal, goal, bond, and flaw is enough for most tables.</p>
                                <ul id="selected-roleplay-list"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-roleplay-notes-title">Table roleplay notes</h3>
                                <p id="selected-roleplay-notes-summary">The broader pacing and social-scene reminders live here so the starter stays short.</p>
                                <ul id="selected-roleplay-notes-list"></ul>
                            </div>
                            <div class="rule-block">
                                <h3 id="selected-appearance-title">Appearance help</h3>
                                <p id="selected-appearance-summary">A few visual anchors are enough: age, height, eyes, hair, and one memorable detail.</p>
                                <ul id="selected-appearance-list"></ul>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Developer context: This section keeps the standalone dice tools separate from character editing and wizard flow. --}}
                {{-- Clear explanation: This block is the dice area for quick rolls and ability-score rolling. --}}
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
                            <input id="dice-expression" type="text" maxlength="50" placeholder="Custom roll like 2d6+3, d20, or 4d8-1">
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

                {{-- Developer context: This section exposes the local rules library without mixing compendium browsing into the build form. --}}
                {{-- Clear explanation: This block is the rules library for browsing reference material. --}}
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
        </div>
        <div class="hover-tooltip" id="hover-tooltip" hidden>
            <strong>Option Details</strong>
            <span>Hover a choice to see what it means.</span>
        </div>

        @php
            $classSheetDetails = [];
            foreach (config('dnd_progressions.classes', []) as $className => $details) {
                $classSheetDetails[$className] = [
                    'saving_throw_proficiencies' => data_get($details, 'traits.saving_throw_proficiencies'),
                    'skill_proficiencies' => data_get($details, 'traits.skill_proficiencies'),
                ];
            }

            $pageData = [
                'configurator' => [
                    'class_details' => config('dnd.class_details'),
                    'species_details' => config('dnd.species_details'),
                    'background_details' => config('dnd.background_details'),
                    'ability_details' => config('dnd.ability_details'),
                    'skill_details' => config('dnd.skill_details'),
                    'alignment_details' => config('dnd.alignment_details'),
                    'alignment_roleplay' => config('dnd.alignment_roleplay'),
                    'advancement_methods' => config('dnd.advancement_methods'),
                    'advancement_method_details' => config('dnd.advancement_method_details'),
                    'roleplay_field_help' => config('dnd.roleplay_field_help'),
                    'roleplay_reference' => config('dnd.roleplay_reference'),
                    'origin_feat_details' => config('dnd.origin_feat_details'),
                    'language_details' => config('dnd.language_details'),
                    'official_rules' => config('dnd.official_rules'),
                    'appearance_field_help' => config('dnd.appearance_field_help'),
                    'form_placeholder_profiles' => config('dnd.form_placeholder_profiles'),
                    'ability_appearance_cues' => config('dnd.ability_appearance_cues'),
                    'class_sheet_details' => $classSheetDetails,
                ],
                'compendium' => config('dnd.compendium'),
                'compendium_sections' => array_values(config('dnd.compendium_sections')),
            ];
        @endphp
        {{-- Developer context: This JSON payload passes trusted PHP-side configuration into the page script without extra requests. --}}
        {{-- Clear explanation: This hidden data block gives the page the information it needs to start up. --}}
        <script id="page-data" type="application/json">{!! json_encode($pageData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        <script>
            // Developer context: This boot block captures the server-provided data and the DOM references the page reuses everywhere else.
            // Clear explanation: These lines load the page data and connect the script to the visible parts of the page.
            const pageData = JSON.parse(document.getElementById('page-data').textContent);
            const configurator = pageData.configurator;
            const compendium = pageData.compendium;
            const compendiumSections = pageData.compendium_sections;
            const countEl = document.getElementById('count');
            const rollEl = document.getElementById('latest-roll');
            const draftNotice = document.getElementById('draft-notice');
            const formNotice = document.getElementById('form-notice');
            const wizardNotice = document.getElementById('wizard-notice');
            const diceNotice = document.getElementById('dice-notice');
            const compendiumNotice = document.getElementById('compendium-notice');
            const previewEl = document.getElementById('preview');
            const hoverTooltip = document.getElementById('hover-tooltip');
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
            const advancementMethodSelect = document.getElementById('advancement_method');
            const classSelect = document.getElementById('class');
            const subclassSelect = document.getElementById('subclass');
            const speciesSelect = document.getElementById('species');
            const backgroundSelect = document.getElementById('background');
            const alignmentSelect = document.getElementById('alignment');
            const originFeatSelect = document.getElementById('origin_feat');
            const languageInputs = Array.from(document.querySelectorAll('input[name="languages[]"]'));
            const skillProficiencyInputs = Array.from(document.querySelectorAll('input[name="skill_proficiencies[]"]'));
            const skillExpertiseInputs = Array.from(document.querySelectorAll('input[name="skill_expertise[]"]'));
            const personalityTraitsInput = document.getElementById('personality_traits');
            const idealsInput = document.getElementById('ideals');
            const goalsInput = document.getElementById('goals');
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
            const classSkillNote = document.getElementById('class-skill-note');
            const expertiseNote = document.getElementById('expertise-note');
            const roleplayPlaceholderNote = document.getElementById('roleplay-placeholder-note');
            const appearancePlaceholderNote = document.getElementById('appearance-placeholder-note');
            const selectedOfficialTitle = document.getElementById('selected-official-title');
            const selectedOfficialSummary = document.getElementById('selected-official-summary');
            const selectedOfficialList = document.getElementById('selected-official-list');
            const compendiumSectionSelect = document.getElementById('compendium-section');
            const compendiumSearchInput = document.getElementById('compendium-search');
            const compendiumTitle = document.getElementById('compendium-title');
            const compendiumSummary = document.getElementById('compendium-summary');
            const compendiumResults = document.getElementById('compendium-results');
            const statFields = ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma'];
            const optionalTextFields = ['alignment', 'personality_traits', 'ideals', 'goals', 'bonds', 'flaws', 'age', 'height', 'weight', 'eyes', 'hair', 'skin', 'notes'];
            // Developer context: This map centralizes browser draft keys so the autosave features share stable storage names.
            // Clear explanation: These labels tell the browser where each draft should be saved locally.
            const localDraftKeys = {
                builder: 'adventurers-ledger.builder-draft.v1',
                wizard: 'adventurers-ledger.wizard-draft.v1',
                dice: 'adventurers-ledger.dice-draft.v1',
                library: 'adventurers-ledger.library-draft.v1',
            };
            // Developer context: These mutable values track the current wizard and dice state between events and redraws.
            // Clear explanation: These lines remember what is happening on the page right now.
            let wizardState = {};
            let wizardPreviewStats = null;
            let currentHoverAnchor = null;
            let wizardMessages = [];
            let lastWizardActions = [];
            let lastWizardSnapshot = null;
            let lastDiceCard = null;
            let localSaveTimer = null;

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
                    // Ignore quota and private-mode write errors so the page stays usable.
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
                    // Ignore private-mode and storage access issues so the page stays usable.
                }
            }

            // Developer context: Schedulelocaldraftsave updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function scheduleLocalDraftSave() {
                clearTimeout(localSaveTimer);
                localSaveTimer = window.setTimeout(persistLocalDrafts, 250);
            }

            // Developer context: Clonedraftvalue updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function cloneDraftValue(value, fallback) {
                try {
                    return JSON.parse(JSON.stringify(value));
                } catch {
                    return fallback;
                }
            }

            // Developer context: Builderdraftfromform updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function builderDraftFromForm() {
                return {
                    name: nameInput.value,
                    class: classSelect.value,
                    subclass: subclassSelect.value,
                    level: levelInput.value,
                    advancement_method: advancementMethodSelect.value,
                    species: speciesSelect.value,
                    background: backgroundSelect.value,
                    alignment: alignmentSelect.value,
                    origin_feat: originFeatSelect.value,
                    languages: languageInputs.filter((input) => input.checked).map((input) => input.value),
                    skill_proficiencies: skillProficiencyInputs.filter((input) => input.checked).map((input) => input.value),
                    skill_expertise: skillExpertiseInputs.filter((input) => input.checked).map((input) => input.value),
                    strength: document.getElementById('strength').value,
                    dexterity: document.getElementById('dexterity').value,
                    constitution: document.getElementById('constitution').value,
                    intelligence: document.getElementById('intelligence').value,
                    wisdom: document.getElementById('wisdom').value,
                    charisma: document.getElementById('charisma').value,
                    personality_traits: personalityTraitsInput.value,
                    ideals: idealsInput.value,
                    goals: goalsInput.value,
                    bonds: bondsInput.value,
                    flaws: flawsInput.value,
                    age: ageInput.value,
                    height: heightInput.value,
                    weight: weightInput.value,
                    eyes: eyesInput.value,
                    hair: hairInput.value,
                    skin: skinInput.value,
                    notes: notesInput.value,
                };
            }

            // Developer context: Applybuilderdraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function applyBuilderDraft(draft) {
                if (! draft || typeof draft !== 'object') {
                    return false;
                }

                form.reset();
                nameInput.value = draft.name || '';
                classSelect.value = draft.class || '';
                populateSubclassOptions(classSelect.value || '', draft.subclass || '');
                subclassSelect.value = draft.subclass || '';
                levelInput.value = draft.level || 1;
                advancementMethodSelect.value = draft.advancement_method || '';
                speciesSelect.value = draft.species || '';
                backgroundSelect.value = draft.background || '';
                alignmentSelect.value = draft.alignment || '';
                originFeatSelect.value = draft.origin_feat || '';

                languageInputs.forEach((input) => {
                    input.checked = Array.isArray(draft.languages) && draft.languages.includes(input.value);
                });
                skillProficiencyInputs.forEach((input) => {
                    input.checked = Array.isArray(draft.skill_proficiencies) && draft.skill_proficiencies.includes(input.value);
                });
                skillExpertiseInputs.forEach((input) => {
                    input.checked = Array.isArray(draft.skill_expertise) && draft.skill_expertise.includes(input.value);
                });

                statFields.forEach((field) => {
                    document.getElementById(field).value = draft[field] || '';
                });

                personalityTraitsInput.value = draft.personality_traits || '';
                idealsInput.value = draft.ideals || '';
                goalsInput.value = draft.goals || '';
                bondsInput.value = draft.bonds || '';
                flawsInput.value = draft.flaws || '';
                ageInput.value = draft.age || '';
                heightInput.value = draft.height || '';
                weightInput.value = draft.weight || '';
                eyesInput.value = draft.eyes || '';
                hairInput.value = draft.hair || '';
                skinInput.value = draft.skin || '';
                notesInput.value = draft.notes || '';
                syncSkillTrainingNotes();
                clearNotice(formNotice);
                renderSelectionReference();
                return true;
            }

            // Developer context: Haswizarddraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function hasWizardDraft(draft) {
                return Boolean(
                    draft
                    && typeof draft === 'object'
                    && (
                        (Array.isArray(draft.messages) && draft.messages.length)
                        || (draft.state && Object.keys(draft.state).length)
                        || (Array.isArray(draft.actions) && draft.actions.length)
                        || draft.snapshot
                        || (typeof draft.input === 'string' && draft.input.trim())
                    ),
                );
            }

            // Developer context: Persistlocaldrafts updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function persistLocalDrafts() {
                const builderDraft = builderDraftFromForm();
                writeLocalDraft(localDraftKeys.builder, builderDraft);

                const wizardDraft = {
                    state: cloneDraftValue(wizardState, {}),
                    messages: cloneDraftValue(wizardMessages.slice(-40), []),
                    actions: cloneDraftValue(lastWizardActions, []),
                    snapshot: cloneDraftValue(lastWizardSnapshot, null),
                    input: wizardInput.value,
                };

                if (hasWizardDraft(wizardDraft)) {
                    writeLocalDraft(localDraftKeys.wizard, wizardDraft);
                } else {
                    removeLocalDraft(localDraftKeys.wizard);
                }

                const diceDraft = {
                    expression: diceExpressionInput.value,
                    mode: diceModeSelect.value,
                    latest_roll: rollEl.textContent,
                    card: cloneDraftValue(lastDiceCard, null),
                };

                if (diceDraft.expression || diceDraft.mode || diceDraft.latest_roll !== 'Ready' || diceDraft.card?.title) {
                    writeLocalDraft(localDraftKeys.dice, diceDraft);
                } else {
                    removeLocalDraft(localDraftKeys.dice);
                }

                const libraryDraft = {
                    section: compendiumSectionSelect.value,
                    search: compendiumSearchInput.value,
                };
                writeLocalDraft(localDraftKeys.library, libraryDraft);
            }

            // Developer context: Restorewizarddraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function restoreWizardDraft() {
                const draft = readLocalDraft(localDraftKeys.wizard);
                if (! hasWizardDraft(draft)) {
                    wizardInput.value = '';
                    return false;
                }

                wizardState = draft.state && typeof draft.state === 'object' ? draft.state : {};
                wizardMessages = Array.isArray(draft.messages)
                    ? draft.messages
                        .filter((entry) => entry && (entry.role === 'user' || entry.role === 'bot'))
                        .map((entry) => ({
                            role: entry.role,
                            text: String(entry.text ?? ''),
                        }))
                    : [];

                wizardLog.innerHTML = '';
                wizardMessages.forEach((entry) => {
                    const article = document.createElement('article');
                    article.className = `wizard-message ${entry.role}`;
                    article.innerHTML = `
                        <div class="wizard-speaker">${entry.role === 'user' ? 'You' : 'Rules Wizard'}</div>
                        <p>${escapeHtml(entry.text).replaceAll('\n', '<br>')}</p>
                    `;
                    wizardLog.appendChild(article);
                });
                wizardLog.scrollTop = wizardLog.scrollHeight;

                wizardInput.value = typeof draft.input === 'string' ? draft.input : '';
                renderWizardActions(Array.isArray(draft.actions) ? draft.actions : []);
                renderWizardSummary(draft.snapshot && typeof draft.snapshot === 'object' ? draft.snapshot : null);
                clearNotice(wizardNotice);
                return true;
            }

            // Developer context: Restoredicedraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function restoreDiceDraft() {
                const draft = readLocalDraft(localDraftKeys.dice);
                if (! draft || typeof draft !== 'object') {
                    return false;
                }

                diceExpressionInput.value = draft.expression || '';
                diceModeSelect.value = draft.mode || '';
                renderDiceResultCard(
                    draft.card?.title || 'Ready to roll',
                    Array.isArray(draft.card?.lines) && draft.card.lines.length
                        ? draft.card.lines
                        : ['Pick any die, use a custom expression, or roll full ability scores from here.'],
                );
                setLatestRoll(draft.latest_roll || 'Ready');
                clearNotice(diceNotice);
                return true;
            }

            // Developer context: Restorelibrarydraft updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function restoreLibraryDraft() {
                const draft = readLocalDraft(localDraftKeys.library);

                if (compendiumSections.length) {
                    const defaultSection = compendiumSections[0]?.key || '';
                    const requestedSection = typeof draft?.section === 'string' ? draft.section : defaultSection;
                    compendiumSectionSelect.value = compendium?.[requestedSection] ? requestedSection : defaultSection;
                }

                compendiumSearchInput.value = typeof draft?.search === 'string' ? draft.search : '';
                renderCompendium();
                return Boolean(draft);
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
                window.setTimeout(() => {
                    clearNotice(draftNotice);
                }, 5000);
            }

            // Developer context: Firsterrormessage updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function firstErrorMessage(payload, fallback) {
                if (! payload || typeof payload !== 'object') {
                    return fallback;
                }

                if (typeof payload.message === 'string' && payload.message.trim()) {
                    return payload.message;
                }

                if (payload.errors && typeof payload.errors === 'object') {
                    for (const value of Object.values(payload.errors)) {
                        if (Array.isArray(value) && typeof value[0] === 'string' && value[0].trim()) {
                            return value[0];
                        }

                        if (typeof value === 'string' && value.trim()) {
                            return value;
                        }
                    }
                }

                return fallback;
            }

            // Developer context: Positionhovertooltip updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function positionHoverTooltip(anchor) {
                if (! hoverTooltip || ! anchor) return;

                const rect = anchor.getBoundingClientRect();
                const tooltipRect = hoverTooltip.getBoundingClientRect();
                const gap = 14;
                let left = rect.right + gap;
                let top = rect.top + ((rect.height - tooltipRect.height) / 2);

                if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = rect.left - tooltipRect.width - gap;
                }

                if (left < 10) {
                    left = Math.max(10, window.innerWidth - tooltipRect.width - 10);
                }

                if (top + tooltipRect.height > window.innerHeight - 10) {
                    top = window.innerHeight - tooltipRect.height - 10;
                }

                if (top < 10) {
                    top = 10;
                }

                hoverTooltip.style.left = `${left}px`;
                hoverTooltip.style.top = `${top}px`;
            }

            // Developer context: Showhoverhelp updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function showHoverHelp(anchor, title, text) {
                if (! hoverTooltip || ! anchor || ! text) {
                    hideHoverHelp();
                    return;
                }

                currentHoverAnchor = anchor;
                hoverTooltip.innerHTML = `<strong>${escapeHtml(title)}</strong><span>${escapeHtml(text)}</span>`;
                hoverTooltip.hidden = false;
                positionHoverTooltip(anchor);
                hoverTooltip.classList.add('show');
            }

            // Developer context: Hidehoverhelp updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function hideHoverHelp() {
                currentHoverAnchor = null;
                if (! hoverTooltip) return;
                hoverTooltip.classList.remove('show');
                hoverTooltip.hidden = true;
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

            // Developer context: Normalizestatscore updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function normalizeStatScore(value) {
                const score = Number(value);
                return Number.isFinite(score) && score > 0 ? score : null;
            }

            // Developer context: Currentformpreviewstats updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function currentFormPreviewStats() {
                const stats = Object.fromEntries(statFields.map((field) => [field, normalizeStatScore(document.getElementById(field).value)]));
                return Object.values(stats).some((value) => value !== null) ? stats : null;
            }

            // Developer context: Selectedskillproficiencies updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function selectedSkillProficiencies() {
                const formValues = skillProficiencyInputs.filter((input) => input.checked).map((input) => input.value);

                if (formValues.length) return formValues;

                return Array.isArray(wizardState?.character?.skill_proficiencies)
                    ? wizardState.character.skill_proficiencies
                    : [];
            }

            // Developer context: Selectedskillexpertise updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function selectedSkillExpertise() {
                const formValues = skillExpertiseInputs.filter((input) => input.checked).map((input) => input.value);

                if (formValues.length) return formValues;

                return Array.isArray(wizardState?.character?.skill_expertise)
                    ? wizardState.character.skill_expertise
                    : [];
            }

            // Developer context: Snapshotpreviewstats updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function snapshotPreviewStats(snapshot) {
                if (! Array.isArray(snapshot?.stats)) return null;

                const stats = {};
                snapshot.stats.forEach((stat) => {
                    const field = statFields.find((entry) => abilityFieldLabel(entry) === stat.label);
                    if (field) stats[field] = normalizeStatScore(stat.score);
                });

                return Object.keys(stats).length ? stats : null;
            }

            // Developer context: Proficiencybonusforlevel updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function proficiencyBonusForLevel(level) {
                const numericLevel = Number(level);
                return Number.isFinite(numericLevel) && numericLevel > 0 ? Math.ceil(numericLevel / 4) + 1 : null;
            }

            // Developer context: Naturaljoin updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function naturalJoin(items) {
                const values = items.filter(Boolean);

                if (! values.length) return '';
                if (values.length === 1) return values[0];
                if (values.length === 2) return `${values[0]} and ${values[1]}`;

                return `${values.slice(0, -1).join(', ')}, and ${values[values.length - 1]}`;
            }

            // Developer context: Trimtooltiptext updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function trimTooltipText(value) {
                return String(value ?? '')
                    .replace(/\s+/g, ' ')
                    .trim()
                    .replace(/[.!?]+$/u, '');
            }

            // Developer context: Lowerfirst updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function lowerFirst(value) {
                const normalized = trimTooltipText(value);

                if (! normalized) return '';

                return normalized.charAt(0).toLowerCase() + normalized.slice(1);
            }

            // Developer context: Tooltiptext updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function tooltipText(...parts) {
                return parts
                    .flat()
                    .map((part) => trimTooltipText(part))
                    .filter(Boolean)
                    .map((part) => `${part}.`)
                    .join(' ');
            }

            // Developer context: Skillexpertisedescription updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function skillExpertiseDescription(skill) {
                return tooltipText(
                    formChoiceDescription('skill', skill),
                    'Expertise doubles your proficiency bonus for that skill',
                );
            }

            // Developer context: Classskillchoicecount updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function classSkillChoiceCount(classValue) {
                const guidance = configurator.class_sheet_details?.[classValue]?.skill_proficiencies || '';
                const match = guidance.match(/choose(?: any)?\s+(\d+)/i);
                return match ? Number(match[1]) : null;
            }

            // Developer context: Classskilloptions updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function classSkillOptions(classValue) {
                const allSkills = Object.keys(configurator.skill_details || {});
                const guidance = configurator.class_sheet_details?.[classValue]?.skill_proficiencies || '';

                if (! classValue || ! guidance) return allSkills;
                if (/choose any/i.test(guidance)) return allSkills;

                const matches = allSkills.filter((skill) => guidance.includes(skill));
                return matches.length ? matches : allSkills;
            }

            // Developer context: Syncskilltrainingnotes updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function syncSkillTrainingNotes() {
                const classValue = classSelect.value;
                const classGuidance = configurator.class_sheet_details?.[classValue]?.skill_proficiencies || '';
                const selectedProficiencies = skillProficiencyInputs.filter((input) => input.checked).map((input) => input.value);
                const choiceCount = classSkillChoiceCount(classValue);
                const options = classSkillOptions(classValue);
                const allowed = new Set(selectedProficiencies);

                skillExpertiseInputs.forEach((input) => {
                    const shouldDisable = ! allowed.has(input.value);
                    input.disabled = shouldDisable;
                    if (shouldDisable) input.checked = false;
                    input.removeAttribute('title');
                });

                const selectedExpertise = skillExpertiseInputs.filter((input) => input.checked).map((input) => input.value);

                if (classSkillNote) {
                    if (classValue && classGuidance) {
                        classSkillNote.textContent = `${classValue}: ${classGuidance}. ${selectedProficiencies.length ? `Selected now: ${selectedProficiencies.join(', ')}.` : 'Pick the skills the sheet should track as proficient.'}`;
                    } else {
                        classSkillNote.textContent = 'Choose at least one skill the sheet should treat as proficient. The class note here will narrow the usual starting choices once you pick a class.';
                    }
                }

                if (expertiseNote) {
                    expertiseNote.textContent = selectedExpertise.length
                        ? `Expertise currently marked on: ${selectedExpertise.join(', ')}. Expertise doubles the proficiency bonus on those checks.`
                        : `Only mark expertise on skills that already have proficiency. ${classValue && choiceCount ? `${classValue} usually starts with ${choiceCount} class skill choice${choiceCount === 1 ? '' : 's'} from this step.` : 'If the build does not get expertise yet, leave this empty.'}`;
                }

                return { choiceCount, options };
            }

            // Developer context: Officialrulesnaturaljoin updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part turns a short list into easier-to-read text.
            function officialRulesNaturalJoin(items) {
                const filtered = items.filter(Boolean);

                if (! filtered.length) return '';
                if (filtered.length === 1) return filtered[0];
                if (filtered.length === 2) return `${filtered[0]} and ${filtered[1]}`;

                return `${filtered.slice(0, -1).join(', ')}, and ${filtered.at(-1)}`;
            }

            // Developer context: Currentofficialruleswarnings updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part builds the non-blocking warnings that explain when the current sheet drifts away from the official 2024 default.
            function currentOfficialRulesWarnings() {
                const official = configurator.official_rules || {};
                const warnings = [];
                const backgroundValue = backgroundSelect.value;
                const classValue = classSelect.value;
                const alignmentValue = alignmentSelect.value;
                const originFeatValue = originFeatSelect.value;
                const advancementMethodValue = advancementMethodSelect.value;
                const languageValues = languageInputs.filter((input) => input.checked).map((input) => input.value);
                const skillValues = skillProficiencyInputs.filter((input) => input.checked).map((input) => input.value);
                const rareLanguages = languageValues.filter((language) => (official.rare_languages || []).includes(language));
                const baselineMethods = official.baseline_advancement_methods || [];
                const hasAnyStats = statFields.some((field) => normalizeStatScore(document.getElementById(field).value) !== null);

                if (advancementMethodValue && baselineMethods.length && ! baselineMethods.includes(advancementMethodValue)) {
                    warnings.push(`${advancementMethodValue} is supported here as a table variant, but the official 2024 baseline in this builder is ${officialRulesNaturalJoin(baselineMethods)}.`);
                }

                if (alignmentValue && (official.evil_alignments || []).includes(alignmentValue) && official.evil_alignment_warning) {
                    warnings.push(official.evil_alignment_warning);
                }

                if (languageValues.length) {
                    const reasons = [];

                    if (! languageValues.includes('Common')) {
                        reasons.push('Common is missing');
                    }

                    if (languageValues.length < 3) {
                        reasons.push(`only ${languageValues.length} language${languageValues.length === 1 ? '' : 's'} ${languageValues.length === 1 ? 'is' : 'are'} selected`);
                    }

                    if (rareLanguages.length) {
                        reasons.push(`${officialRulesNaturalJoin(rareLanguages)} ${rareLanguages.length === 1 ? 'is' : 'are'} rare`);
                    }

                    if (reasons.length && official.language_warning) {
                        warnings.push(`${reasons[0].charAt(0).toUpperCase()}${reasons[0].slice(1)}${reasons.length > 1 ? `, ${reasons.slice(1).join(', ')}` : ''}. ${official.language_warning}`);
                    }
                }

                if (backgroundValue && (originFeatValue || skillValues.length || languageValues.length || hasAnyStats) && official.background_package_warning) {
                    warnings.push(official.background_package_warning);
                }

                if (backgroundValue && skillValues.length && official.skill_package_warning) {
                    warnings.push(official.skill_package_warning);
                }

                if ((backgroundValue || classValue) && official.tool_equipment_warning) {
                    warnings.push(official.tool_equipment_warning);
                }

                return [...new Set(warnings)];
            }

            // Developer context: Abilityrelatedskills updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function abilityRelatedSkills(field) {
                const label = abilityFieldLabel(field);

                return Object.entries(configurator.skill_details || {})
                    .filter(([, detail]) => detail?.ability === label)
                    .map(([name]) => name);
            }

            // Developer context: Abilityhoverdescription updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function abilityHoverDescription(field) {
                const label = abilityFieldLabel(field);
                const previewStats = currentFormPreviewStats() || wizardPreviewStats || {};
                const score = normalizeStatScore(previewStats[field]);
                const modifier = score === null ? null : abilityModifier(score);
                const classValue = classSelect.value || wizardState?.character?.class || '';
                const classDetail = configurator.class_details?.[classValue] || null;
                const sheetDetail = configurator.class_sheet_details?.[classValue] || null;
                const saveProficiencies = String(sheetDetail?.saving_throw_proficiencies || '');
                const hasSaveProficiency = Boolean(classValue && saveProficiencies.includes(label));
                const levelValue = Number(levelInput.value) || Number(wizardState?.character?.level) || null;
                const proficiencyBonus = proficiencyBonusForLevel(levelValue);
                const saveModifier = modifier === null ? null : modifier + (hasSaveProficiency && proficiencyBonus ? proficiencyBonus : 0);
                const relatedSkills = abilityRelatedSkills(field);
                const selectedProficientSkills = selectedSkillProficiencies().filter((skill) => relatedSkills.includes(skill));
                const selectedExpertiseSkills = selectedSkillExpertise().filter((skill) => relatedSkills.includes(skill));
                const selectedNormalSkills = selectedProficientSkills.filter((skill) => ! selectedExpertiseSkills.includes(skill));
                const isClassFocus = Array.isArray(classDetail?.primary_focus) && classDetail.primary_focus.includes(label);

                return tooltipText(
                    score === null
                        ? `${label} has not been set on the sheet yet`
                        : `${label} is currently ${score}, which gives it a ${formatModifier(modifier)} modifier`,
                    hasSaveProficiency && saveModifier !== null
                        ? `${label} saves are proficient for ${classValue}, so the current save bonus is ${formatModifier(saveModifier)}`
                        : (saveModifier !== null ? `${label} saves are not proficient right now, so the current save bonus is ${formatModifier(saveModifier)}` : ''),
                    relatedSkills.length
                        ? `${label} feeds into ${naturalJoin(relatedSkills)}`
                        : `No standard skills key off ${label}`,
                    selectedNormalSkills.length
                        ? `You already have proficiency in ${naturalJoin(selectedNormalSkills)}`
                        : '',
                    selectedExpertiseSkills.length
                        ? `You already have expertise in ${naturalJoin(selectedExpertiseSkills)}`
                        : '',
                    isClassFocus ? `${label} is one of ${classValue}'s main focus abilities` : '',
                    relatedSkills.length && ! selectedNormalSkills.length && ! selectedExpertiseSkills.length
                        ? `No trained ${label.toLowerCase()} skills are marked on the sheet yet`
                        : '',
                );
            }

            // Developer context: Syncabilitypreview updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function syncAbilityPreview() {
                renderPreview(currentFormPreviewStats() || wizardPreviewStats);
            }

            // Developer context: Renderpreview updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function renderPreview(stats) {
                const shortLabels = {
                    strength: 'STR',
                    dexterity: 'DEX',
                    constitution: 'CON',
                    intelligence: 'INT',
                    wisdom: 'WIS',
                    charisma: 'CHA',
                };

                previewEl.innerHTML = statFields.map((field) => {
                    const score = normalizeStatScore(stats?.[field]);
                    const modifierText = score === null ? '--' : formatModifier(abilityModifier(score));
                    const scoreText = score === null ? '--' : String(score);
                    const description = abilityHoverDescription(field);

                    const input = document.getElementById(field);
                    if (input) input.removeAttribute('title');

                    return `
                        <div class="stat preview-stat" tabindex="0" data-preview-field="${field}">
                            <span class="label">${shortLabels[field]}</span>
                            <span class="value">${scoreText}</span>
                            <span class="preview-mod">Mod ${modifierText}</span>
                        </div>
                    `;
                }).join('');
            }

            // Developer context: Abilityfieldlabel updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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

            // Developer context: Abilitymodifier updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function abilityModifier(score) {
                return Math.floor((Number(score) - 10) / 2);
            }

            // Developer context: Formatmodifier updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function formatModifier(modifier) {
                return modifier >= 0 ? `+${modifier}` : String(modifier);
            }

            // Developer context: Currentappearancecues updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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

            // Developer context: Backgrounddrivenbondplaceholder updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function backgroundDrivenBondPlaceholder(backgroundValue, fallback) {
                const theme = configurator.background_details?.[backgroundValue]?.theme;

                if (! theme) return fallback;

                return `A person, place, or promise tied to ${theme.toLowerCase()}...`;
            }

            // Developer context: Classdriventraitplaceholder updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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

            // Developer context: Currentstatextremes updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function currentStatExtremes() {
                const stats = statFields.map((field) => ({
                    field,
                    score: Number(document.getElementById(field).value),
                })).filter((entry) => Number.isFinite(entry.score) && entry.score > 0);

                if (! stats.length) {
                    return { highest: null, lowest: null };
                }

                const highest = [...stats].sort((a, b) => b.score - a.score)[0]?.field || null;
                const lowest = [...stats].sort((a, b) => a.score - b.score)[0]?.field || null;

                return { highest, lowest };
            }

            // Developer context: Compactstarter updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function compactStarter(base, extras = []) {
                return tooltipText(base, ...extras.filter(Boolean).slice(0, 2));
            }

            // Developer context: Advancementroleplaysummary updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function advancementRoleplaySummary(method, cue) {
                if (! method || ! cue) {
                    return '';
                }

                const trimmedCue = cue.trim();

                if (/^growth feels tied to\s+/i.test(trimmedCue)) {
                    return `${method} ties growth to ${trimmedCue.replace(/^growth feels tied to\s+/i, '')}`;
                }

                return `${method} means ${lowerFirst(trimmedCue)}`;
            }

            // Developer context: Combinedroleplaystarterpackage updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function combinedRoleplayStarterPackage() {
                const classValue = classSelect.value;
                const speciesValue = speciesSelect.value;
                const backgroundValue = backgroundSelect.value;
                const alignmentValue = alignmentSelect.value;
                const originFeatValue = originFeatSelect.value;
                const advancementMethodValue = advancementMethodSelect.value;
                const languageValues = languageInputs.filter((input) => input.checked).map((input) => input.value);
                const skillValues = skillProficiencyInputs.filter((input) => input.checked).map((input) => input.value);
                const classDetail = configurator.class_details?.[classValue] || null;
                const speciesDetail = configurator.species_details?.[speciesValue] || null;
                const backgroundDetail = configurator.background_details?.[backgroundValue] || null;
                const alignmentDetail = configurator.alignment_details?.[alignmentValue] || '';
                const alignmentRoleplay = configurator.alignment_roleplay?.[alignmentValue] || null;
                const originFeatDetail = configurator.origin_feat_details?.[originFeatValue] || '';
                const advancementDetail = configurator.advancement_method_details?.[advancementMethodValue] || null;
                const focusAbilities = Array.isArray(classDetail?.primary_focus) ? classDetail.primary_focus : [];
                const speciesTraits = Array.isArray(speciesDetail?.traits) ? speciesDetail.traits.slice(0, 2) : [];
                const backgroundTheme = backgroundDetail?.theme ? backgroundDetail.theme.toLowerCase() : '';
                const advancementCue = advancementDetail?.roleplay_cue || '';
                const speciesTraitText = speciesTraits.length ? naturalJoin(speciesTraits).toLowerCase() : '';
                const trainedSkillText = skillValues.length ? naturalJoin(skillValues.slice(0, 2)).toLowerCase() : '';
                const { highest, lowest } = currentStatExtremes();
                const highestLabel = highest ? abilityFieldLabel(highest) : '';
                const lowestLabel = lowest ? abilityFieldLabel(lowest) : '';
                const focusText = focusAbilities.length ? naturalJoin(focusAbilities.map((ability) => ability.toLowerCase())) : '';
                const languageText = languageValues.length ? naturalJoin(languageValues.map((language) => language.toLowerCase())) : '';
                const titleParts = [alignmentValue, speciesValue, backgroundValue, classValue].filter(Boolean);
                const sourceParts = [
                    alignmentValue ? `alignment (${alignmentValue})` : '',
                    speciesValue ? `species (${speciesValue})` : '',
                    backgroundValue ? `background (${backgroundValue})` : '',
                    classValue ? `class (${classValue})` : '',
                    originFeatValue ? `origin feat (${originFeatValue})` : '',
                    advancementMethodValue ? `advancement (${advancementMethodValue})` : '',
                    highestLabel ? `${highestLabel.toLowerCase()} as the strongest score` : '',
                ].filter(Boolean);

                return {
                    title: titleParts.length ? `${titleParts.join(' ')} roleplay starter` : 'Beginner roleplay help',
                    summary: tooltipText(
                        titleParts.length ? `This build reads like a ${titleParts.join(' ')}` : '',
                        alignmentRoleplay?.play_well || (alignmentDetail ? `At heart, the character ${lowerFirst(alignmentDetail)}` : ''),
                        backgroundTheme ? `${backgroundValue} gives their choices a ${backgroundTheme} pull` : '',
                        classValue && focusText ? `${classValue} nudges them toward ${focusText} when things get tense` : '',
                        speciesValue && speciesTraitText ? `${speciesValue} leaves a trace in details like ${speciesTraitText}` : '',
                        originFeatValue ? `${originFeatValue} shapes the way they first come across` : '',
                        advancementRoleplaySummary(advancementMethodValue, advancementCue),
                    ),
                    trait: compactStarter(
                        alignmentRoleplay?.starter_trait || classDrivenTraitPlaceholder(classValue, 'Short first-impression notes like calm, curious, dry humor...'),
                        [
                            classValue && focusText ? `When pressure hits, my ${classValue.toLowerCase()} instincts push me toward ${focusText}` : (classValue ? `My ${classValue.toLowerCase()} training still shows whenever the room gets tense` : ''),
                            backgroundTheme ? `Years shaped by ${backgroundTheme} still show in the way I carry myself` : '',
                            advancementMethodValue && advancementCue ? `The way I talk about growth is shaped by ${advancementMethodValue.toLowerCase()}: ${advancementCue}` : '',
                            trainedSkillText ? `People quickly notice that I approach problems through ${trainedSkillText}` : '',
                            highestLabel ? `${highestLabel} is usually the first part of me people notice` : '',
                            speciesValue ? (speciesTraitText ? `You can still hear my ${speciesValue.toLowerCase()} roots in the ${speciesTraitText} side of me` : `Being ${speciesValue.toLowerCase()} still shapes how I come across to other people`) : '',
                        ],
                    ),
                    ideal: compactStarter(
                        alignmentRoleplay?.starter_ideal || (backgroundTheme ? `${backgroundTheme} matters more than comfort` : 'I want to live by a principle that actually means something.'),
                        [
                            classValue ? `Being a ${classValue.toLowerCase()} keeps asking what my gifts are actually for` : '',
                            backgroundTheme ? `To me, ${backgroundTheme} only matters if it means something in the real world` : '',
                            originFeatValue ? `${originFeatValue} feels like something I should use with purpose, not vanity` : '',
                            advancementMethodValue ? `I measure progress through ${advancementMethodValue.toLowerCase()} rather than chasing empty motion` : '',
                        ],
                    ),
                    goal: compactStarter(
                        backgroundTheme ? `I want to turn my ${backgroundTheme} past into something that still matters` : 'I want to accomplish something that will still matter after this adventure ends',
                        [
                            classValue ? `Part of me wants to prove what a ${classValue.toLowerCase()} can really accomplish` : '',
                            originFeatValue ? `${originFeatValue} feels like the start of something I am meant to build on` : '',
                            advancementMethodValue ? `I want my progress to feel earned through ${advancementMethodValue.toLowerCase()}` : '',
                            highestLabel ? `My strongest ${highestLabel.toLowerCase()} keeps pulling me toward that goal` : '',
                        ],
                    ),
                    bond: compactStarter(
                        alignmentRoleplay?.starter_bond || backgroundDrivenBondPlaceholder(backgroundValue, 'Who or what matters enough to change their decisions?'),
                        [
                            backgroundValue ? `${backgroundValue} roots still tie me to the people and places that made me` : '',
                            languageText ? `Speaking ${languageText} keeps me connected to more than one corner of the world` : '',
                            speciesValue ? `Part of me still feels answerable to what it means to be ${speciesValue.toLowerCase()}` : '',
                            originFeatValue ? `I still remember who first helped me turn ${originFeatValue} into something useful` : '',
                            advancementMethodValue ? `How I grow now is tied to the table through ${advancementMethodValue.toLowerCase()}` : '',
                        ],
                    ),
                    flaw: compactStarter(
                        alignmentRoleplay?.starter_flaw || (classValue ? `The harder I lean into being a ${classValue.toLowerCase()}, the easier it is for one bad habit to take over` : 'Under pressure, one bad habit can steer the moment'),
                        [
                            lowestLabel ? `Under strain, my lower ${lowestLabel.toLowerCase()} is usually the first crack to show` : '',
                            classValue ? `When I lean too hard on my ${classValue.toLowerCase()} instincts, I can miss gentler answers` : '',
                            backgroundTheme ? `Old habits from a life shaped by ${backgroundTheme} can make me dig in too hard` : '',
                        ],
                    ),
                    watchOut: alignmentRoleplay?.watch_out || '',
                    progression: advancementMethodValue && advancementCue ? `${advancementMethodValue}: ${advancementCue}` : '',
                    sources: sourceParts,
                };
            }

            // Developer context: Updateadaptiveplaceholders updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function updateAdaptivePlaceholders() {
                const profiles = configurator.form_placeholder_profiles || {};
                const defaultProfile = profiles.default || {};
                const speciesProfile = profiles.species?.[speciesSelect.value] || {};
                const focus = configurator.class_details?.[classSelect.value]?.primary_focus || [];
                const roleplayStarter = combinedRoleplayStarterPackage();

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

                personalityTraitsInput.placeholder = roleplayStarter.trait || classDrivenTraitPlaceholder(classSelect.value, 'Short first-impression notes like calm, curious, dry humor...');
                idealsInput.placeholder = roleplayStarter.ideal || 'What principle matters most to this character?';
                goalsInput.placeholder = roleplayStarter.goal || 'What does this character want to achieve, protect, prove, or uncover next?';
                bondsInput.placeholder = roleplayStarter.bond || backgroundDrivenBondPlaceholder(backgroundSelect.value, 'Who or what matters enough to change their decisions?');
                flawsInput.placeholder = roleplayStarter.flaw || (classSelect.value ? `A ${classSelect.value.toLowerCase()} habit that sometimes causes trouble...` : 'What weakness or habit tends to cause trouble?');

                const notesBits = [
                    classSelect.value ? `${classSelect.value} hooks` : '',
                    backgroundSelect.value ? `${backgroundSelect.value} ties` : '',
                    speciesSelect.value ? `${speciesSelect.value} details` : '',
                    goalsInput.value.trim() ? 'goal thread' : '',
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
                    roleplayPlaceholderNote.textContent = roleplayStarter.sources.length
                        ? `Roleplay starters are blending ${roleplayStarter.sources.join(', ')}. Treat them as prompts, not limits.`
                        : 'Roleplay starters combine alignment, goals, social-scene guidance, species, background, class, table pacing, and the rest of the sheet once the build starts to come together.';
                }

                if (appearancePlaceholderNote) {
                    appearancePlaceholderNote.textContent = speciesSelect.value
                        ? `Appearance examples are flavored for ${speciesSelect.value}. They are lore-style examples, not hard limits.`
                        : (defaultProfile.appearance_note || 'Appearance examples shift with species. They are lore-style examples, not hard limits.');
                }
            }

            // Developer context: Setlatestroll updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function setLatestRoll(label) {
                rollEl.textContent = String(label || 'Ready').slice(0, 28);
            }

            // Developer context: Renderdiceresultcard updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function renderDiceResultCard(title, lines = []) {
                lastDiceCard = {
                    title,
                    lines: Array.isArray(lines) ? [...lines] : [],
                };
                diceResult.innerHTML = `
                    <h3>${escapeHtml(title)}</h3>
                    ${lines.map((line) => `<p>${escapeHtml(line)}</p>`).join('')}
                `;
            }

            // Developer context: Renderdiceexpressionresult updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function renderDiceExpressionResult(data) {
                const modeLabel = data.mode ? ` (${data.mode})` : '';
                renderDiceResultCard(
                    `${data.expression}${modeLabel} = ${data.total}`,
                    [data.detail || 'Roll complete.'],
                );
                setLatestRoll(`${data.expression}: ${data.total}`);
                scheduleLocalDraftSave();
            }

            // Developer context: Renderdicestatsresult updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function renderDiceStatsResult(stats, { populateForm = false } = {}) {
                if (populateForm) {
                    statFields.forEach((field) => document.getElementById(field).value = stats[field]);
                    syncAbilityPreview();
                }

                const summary = statFields
                    .map((field) => `${field.slice(0, 3).toUpperCase()} ${stats[field]}`)
                    .join(' | ');

                renderDiceResultCard('Ability Scores Rolled', [summary, 'Rolled as 4d6 and dropped the lowest die for each stat.']);
                setLatestRoll(summary);
                scheduleLocalDraftSave();
            }

            // Developer context: Appendwizardmessage updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function appendWizardMessage(role, text) {
                const entry = {
                    role,
                    text: String(text ?? ''),
                };
                wizardMessages.push(entry);
                const article = document.createElement('article');
                article.className = `wizard-message ${role}`;
                article.innerHTML = `
                    <div class="wizard-speaker">${role === 'user' ? 'You' : 'Rules Wizard'}</div>
                    <p>${escapeHtml(entry.text).replaceAll('\n', '<br>')}</p>
                `;
                wizardLog.appendChild(article);
                wizardLog.scrollTop = wizardLog.scrollHeight;
                scheduleLocalDraftSave();
            }

            // Developer context: Wizardactiondescription updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function wizardActionDescription(action) {
                const pendingField = wizardState?.pending_field;

                if (! pendingField) return '';
                if (action === 'skip') return 'Leave this optional detail blank for now. You can always come back to it later.';
                if (action === 'skip all details') return 'Wrap up the core sheet now and come back to the optional details when you are ready.';
                if (action === 'random that fits') return 'Generate a fitting random suggestion from the choices already locked into the sheet. You can reroll until you like it.';
                if (action === 'keep this') return 'Accept the current random suggestion and move to the next wizard step.';
                if (action === 'reroll random') return 'Generate another fitting random suggestion for this same field.';
                if (action === 'keep these scores') return 'Accept this rolled set of ability scores and continue the wizard.';
                if (action === 'reroll ability scores') return 'Roll a fresh six-score set. You can keep rerolling until the set feels right.';

                if (pendingField === 'species') {
                    return formChoiceDescription('species', action);
                }

                if (pendingField === 'class') {
                    return formChoiceDescription('class', action);
                }

                if (pendingField === 'background') {
                    return formChoiceDescription('background', action);
                }

                if (pendingField === 'alignment') {
                    return formChoiceDescription('alignment', action);
                }

                if (pendingField === 'origin_feat') {
                    return formChoiceDescription('origin_feat', action);
                }

                if (pendingField === 'subclass') {
                    return formChoiceDescription('subclass', action);
                }

                return '';
            }

            // Developer context: Formchoicedescription updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function formChoiceDescription(field, value) {
                if (! value) return '';

                if (field === 'class') {
                    const detail = configurator.class_details?.[value];
                    return detail
                        ? tooltipText(
                            detail.summary,
                            Array.isArray(detail.primary_focus) && detail.primary_focus.length
                                ? `${value} usually leans on ${naturalJoin(detail.primary_focus)}`
                                : '',
                        )
                        : '';
                }

                if (field === 'subclass') {
                    const currentClass = classSelect.value || wizardState?.character?.class || '';
                    return tooltipText(
                        currentClass
                            ? `${value} is a subclass for ${currentClass}`
                            : `${value} is a subclass option`,
                        'It narrows the class into a more specific play style',
                    );
                }

                if (field === 'species') {
                    const detail = configurator.species_details?.[value];
                    return detail
                        ? tooltipText(
                            detail.summary,
                            detail.size ? `${value} characters are usually ${detail.size}` : '',
                            detail.speed ? `Their speed is ${detail.speed}` : '',
                        )
                        : '';
                }

                if (field === 'background') {
                    const detail = configurator.background_details?.[value];
                    return detail
                        ? tooltipText(
                            detail.summary,
                            detail.theme ? `Its theme centers on ${detail.theme.toLowerCase()}` : '',
                        )
                        : '';
                }

                if (field === 'alignment') {
                    const detail = configurator.alignment_details?.[value];
                    return detail
                        ? tooltipText(`${value} usually means the character ${lowerFirst(detail)}`)
                        : '';
                }

                if (field === 'origin_feat') {
                    const detail = configurator.origin_feat_details?.[value];
                    return detail
                        ? tooltipText(`${value} ${lowerFirst(detail)}`)
                        : '';
                }

                if (field === 'advancement_method') {
                    const detail = configurator.advancement_method_details?.[value];
                    return detail
                        ? tooltipText(
                            detail.summary || `${value} sets the table pace for level-ups.`,
                            detail.play_note || '',
                        )
                        : '';
                }

                if (field === 'language') {
                    const detail = configurator.language_details?.[value];
                    return detail
                        ? tooltipText(`${value} is ${lowerFirst(detail)}`)
                        : '';
                }

                if (field === 'skill') {
                    const detail = configurator.skill_details?.[value];
                    return detail
                        ? tooltipText(
                            `${value} covers ${lowerFirst(detail.summary)}`,
                            detail.ability ? `It usually uses ${detail.ability}` : '',
                        )
                        : '';
                }

                return '';
            }

            // Developer context: Renderwizardactions updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function renderWizardActions(actions) {
                lastWizardActions = Array.isArray(actions) ? [...actions] : [];
                wizardActions.innerHTML = '';
                const richActions = lastWizardActions.some((action) => wizardActionDescription(action));
                wizardActions.classList.toggle('rich', richActions);
                hideHoverHelp();

                lastWizardActions.slice(0, 12).forEach((action) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = `btn-soft${richActions ? ' wizard-option' : ''}`;
                    button.dataset.wizardAction = action;
                    const description = wizardActionDescription(action);
                    button.textContent = action;
                    button.removeAttribute('title');
                    if (description) {
                        button.addEventListener('mouseenter', () => showHoverHelp(button, action, description));
                        button.addEventListener('focus', () => showHoverHelp(button, action, description));
                        button.addEventListener('mouseleave', hideHoverHelp);
                        button.addEventListener('blur', hideHoverHelp);
                    }
                    wizardActions.appendChild(button);
                });
                scheduleLocalDraftSave();
            }

            // Developer context: Syncselecttitle updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function syncSelectTitle(control) {
                control.removeAttribute('title');
            }

            // Developer context: Wirechoicehelp updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function wireChoiceHelp(control, field) {
                const show = () => {
                    const value = control.value;
                    const description = formChoiceDescription(field, value);
                    syncSelectTitle(control);
                    if (! value || ! description) {
                        hideHoverHelp();
                        return;
                    }

                    showHoverHelp(control, value, description);
                };

                control.addEventListener('mouseenter', show);
                control.addEventListener('focus', show);
                control.addEventListener('change', show);
                control.addEventListener('mouseleave', hideHoverHelp);
                control.addEventListener('blur', hideHoverHelp);
            }

            // Developer context: Wireabilityhelp updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function wireAbilityHelp(field) {
                const input = document.getElementById(field);
                if (! input) return;

                const show = () => {
                    const description = abilityHoverDescription(field);
                    input.removeAttribute('title');
                    showHoverHelp(input, abilityFieldLabel(field), description);
                };

                input.addEventListener('mouseenter', show);
                input.addEventListener('focus', show);
                input.addEventListener('input', () => {
                    input.removeAttribute('title');
                    if (currentHoverAnchor === input) show();
                });
                input.addEventListener('mouseleave', hideHoverHelp);
                input.addEventListener('blur', hideHoverHelp);
            }

            // Developer context: Filtertryasking updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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

            // Developer context: Renderwizardsummary updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function renderWizardSummary(snapshot) {
                lastWizardSnapshot = snapshot && typeof snapshot === 'object'
                    ? cloneDraftValue(snapshot, null)
                    : null;

                if (! snapshot || ! snapshot.identity) {
                    wizardPreviewStats = null;
                    syncAbilityPreview();
                    wizardSummary.innerHTML = `
                        <div class="wizard-summary-card">
                            <h3>No active character</h3>
                            <p>The wizard will show the current sheet, combat state, features, and next-level preview here.</p>
                        </div>
                    `;
                    scheduleLocalDraftSave();
                    return;
                }

                wizardPreviewStats = snapshotPreviewStats(snapshot);
                syncAbilityPreview();

                const stats = (snapshot.stats || []).map((stat) => `
                    <li>${escapeHtml(stat.label)}: ${stat.score ?? '-'}${stat.modifier ? ` (${escapeHtml(stat.modifier)})` : ''}</li>
                `).join('');

                const currentFeatures = (snapshot.current_features || []).map((feature) => `<li>${escapeHtml(feature)}</li>`).join('');
                const nextGains = (snapshot.next_gains || []).map((feature) => `<li>${escapeHtml(feature)}</li>`).join('');
                const missing = (snapshot.missing_fields || []).map((field) => `<li>${escapeHtml(field)}</li>`).join('');
                const characterDetails = (snapshot.character_details || []).join(' / ');
                const languages = (snapshot.languages || []).join(', ');
                const skillProficiencies = (snapshot.skill_proficiencies || []).join(', ');
                const skillExpertise = (snapshot.skill_expertise || []).join(', ');
                const roleplay = (snapshot.roleplay || []).map((entry) => `<li>${escapeHtml(entry)}</li>`).join('');
                const appearance = (snapshot.appearance || []).map((entry) => `<li>${escapeHtml(entry)}</li>`).join('');
                const notes = snapshot.notes ? escapeHtml(snapshot.notes) : '';
                const officialWarnings = (snapshot.official_rules_warnings || []).map((warning) => `<li>${escapeHtml(warning)}</li>`).join('');
                const conditions = (snapshot.conditions || []).map((condition) => `<li>Condition: ${escapeHtml(condition)}</li>`).join('');
                const resources = (snapshot.resources || []).map((resource) => `<li>${escapeHtml(resource)}</li>`).join('');
                const concentration = snapshot.concentration ? `<li>Concentration: ${escapeHtml(snapshot.concentration)}</li>` : '';
                const deathTrack = snapshot.death_track ? `<li>${escapeHtml(snapshot.death_track)}</li>` : '';
                const dungeonStatus = snapshot.dungeon_status ? `<li>${escapeHtml(snapshot.dungeon_status)}</li>` : '<li>Dungeon state is waiting on the active build.</li>';

                wizardSummary.innerHTML = `
                    <div class="wizard-summary-card">
                        <h3>${escapeHtml(snapshot.identity)}</h3>
                        <p>${snapshot.proficiency_bonus ? `Proficiency Bonus ${escapeHtml(snapshot.proficiency_bonus)}` : 'Proficiency Bonus pending'}</p>
                        <p>${snapshot.hit_point_value !== null ? `${escapeHtml(snapshot.hit_point_label || 'Estimated HP')} ${escapeHtml(String(snapshot.hit_point_value))}` : 'Estimated HP pending'}</p>
                        <p>${snapshot.spellcasting_summary ? escapeHtml(snapshot.spellcasting_summary) : 'No spellcasting summary yet'}</p>
                        <p>${characterDetails ? escapeHtml(characterDetails) : 'Core identity details like class, species, background, and origin feat are not set yet.'}</p>
                        <p>${skillProficiencies ? `Skills: ${escapeHtml(skillProficiencies)}` : 'Skill proficiencies are not set yet.'}</p>
                        <p>${skillExpertise ? `Expertise: ${escapeHtml(skillExpertise)}` : 'No expertise is marked on the sheet yet.'}</p>
                        <p>${languages ? `Languages: ${escapeHtml(languages)}` : 'Languages are not set yet.'}</p>
                        <p>${notes ? `Notes: ${notes}` : 'Notes are not set yet.'}</p>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Dungeon State</h3>
                        <ul>${dungeonStatus}${conditions}${resources}${concentration}${deathTrack}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Official 2024 warnings</h3>
                        <ul>${officialWarnings || '<li>No official-rules warnings right now.</li>'}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Stats</h3>
                        <ul>${stats || '<li>No stats set yet.</li>'}</ul>
                    </div>
                    <div class="wizard-summary-card">
                        <h3>Roleplay</h3>
                        <ul>${roleplay || '<li>No trait, ideal, goal, bond, or flaw set yet.</li>'}</ul>
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
                scheduleLocalDraftSave();
            }

            // Developer context: Rendercompendium updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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
                    scheduleLocalDraftSave();
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
                scheduleLocalDraftSave();
            }

            // Developer context: Populatesubclassoptions updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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

            // Developer context: Renderselectionreference updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function renderSelectionReference() {
                updateAdaptivePlaceholders();
                const { choiceCount: classSkillChoiceTotal, options: classSkillOptionsList } = syncSkillTrainingNotes();
                syncAbilityPreview();

                const nameValue = nameInput.value.trim();
                const classValue = classSelect.value;
                const subclassValue = subclassSelect.value;
                const speciesValue = speciesSelect.value;
                const backgroundValue = backgroundSelect.value;
                const alignmentValue = alignmentSelect.value;
                const originFeatValue = originFeatSelect.value;
                const languageValues = languageInputs.filter((input) => input.checked).map((input) => input.value);
                const skillProficiencyValues = skillProficiencyInputs.filter((input) => input.checked).map((input) => input.value);
                const skillExpertiseValues = skillExpertiseInputs.filter((input) => input.checked).map((input) => input.value);
                const levelValue = levelInput.value.trim();
                const advancementMethodValue = advancementMethodSelect.value;
                const personalityTraitsValue = personalityTraitsInput.value.trim();
                const idealsValue = idealsInput.value.trim();
                const goalsValue = goalsInput.value.trim();
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
                const advancementDetail = configurator.advancement_method_details?.[advancementMethodValue];
                const focusAbilities = Array.isArray(classDetail?.primary_focus) ? classDetail.primary_focus : [];
                const languageDetails = languageValues.map((language) => ({
                    name: language,
                    summary: configurator.language_details?.[language] || '',
                }));
                const appearanceCues = currentAppearanceCues();
                const roleplayStarter = combinedRoleplayStarterPackage();
                const officialRulesWarnings = currentOfficialRulesWarnings();
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
                    advancementMethodValue ? `Advancement ready: ${advancementMethodValue}` : 'Advancement method still needed',
                    subclassValue ? `Subclass ready: ${subclassValue}` : 'Subclass still needed',
                    skillProficiencyValues.length ? `Skill training ready: ${skillProficiencyValues.join(', ')}` : 'Skill training still needed',
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
                    ! advancementMethodValue ? 'advancement method' : '',
                    ! subclassValue ? 'subclass' : '',
                    ! skillProficiencyValues.length ? 'skill training' : '',
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
                    ? `Step focus: class and skill training first, origin next, then scores. ${classValue} usually leans on ${focusAbilities.join(', ')}.${levelValue ? ` Level ${levelValue} is selected.` : ''}${advancementMethodValue ? ` Table pacing: ${advancementMethodValue}.` : ''}`
                    : `${levelValue ? `Level ${levelValue} is selected.` : 'Pick a class and level to see focus guidance.'}${advancementMethodValue ? ` Table pacing: ${advancementMethodValue}.` : ''}`;
                document.getElementById('selected-build-checklist').innerHTML = coreChecklist.map((entry) => `<li>${escapeHtml(entry)}</li>`).join('');

                selectedOfficialTitle.textContent = officialRulesWarnings.length ? 'Official 2024 warnings' : 'Official 2024 check';
                selectedOfficialSummary.textContent = officialRulesWarnings.length
                    ? 'The current sheet still works here, but these choices need a manual rules check if you want to stay strictly official.'
                    : 'No official-rules warnings are active right now. The builder is still flexible, but nothing selected here is currently drifting away from the checked 2024 baseline.';
                selectedOfficialList.innerHTML = officialRulesWarnings.length
                    ? officialRulesWarnings.map((warning) => `<li>${escapeHtml(warning)}</li>`).join('')
                    : '<li>No official-rules warnings right now.</li>';

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
                    ? `Primary focus: ${classDetail.primary_focus.join(', ')}${subclassValue ? ` | Selected subclass: ${subclassValue}` : ''}${classSkillChoiceTotal ? ` | Typical skill choices: ${classSkillChoiceTotal}` : ''}`
                    : 'Primary focus and playstyle notes will appear here.';
                document.getElementById('selected-subclasses').innerHTML = (classDetail?.subclasses || []).length
                    ? (classDetail.subclasses || []).map((subclass) => `<li>${escapeHtml(subclass)}${subclassValue === subclass ? ' <span class="tiny">(selected)</span>' : ''}</li>`).join('')
                    : '<li>Subclass options will appear after you choose a class.</li>';

                document.getElementById('selected-skills-title').textContent = skillProficiencyValues.length
                    ? `Skill training (${skillProficiencyValues.length})`
                    : 'Skill training';
                document.getElementById('selected-skills-summary').textContent = classValue && classSkillOptionsList.length
                    ? `${classValue} usually points you toward ${classSkillOptionsList.length > 8 ? `${classSkillOptionsList.slice(0, 8).join(', ')}, and more` : classSkillOptionsList.join(', ')}.${skillExpertiseValues.length ? ` Expertise marked on ${skillExpertiseValues.join(', ')}.` : ' Expertise stays optional unless something on the sheet grants it.'}`
                    : 'Choose the skills the sheet should treat as proficient, then add expertise only where something on the sheet grants it.';
                document.getElementById('selected-skills-list').innerHTML = skillProficiencyValues.length
                    ? [
                        `<li><strong>Proficient:</strong> ${escapeHtml(skillProficiencyValues.join(', '))}</li>`,
                        skillExpertiseValues.length ? `<li><strong>Expertise:</strong> ${escapeHtml(skillExpertiseValues.join(', '))}</li>` : '<li><strong>Expertise:</strong> none marked on the sheet yet.</li>',
                    ].join('')
                    : '<li>Choose at least one skill proficiency so the sheet can calculate trained checks and show them on ability hover.</li>';

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

                const roleplayNotes = [
                    roleplayStarter.progression ? `<li><strong>Campaign pace:</strong> ${escapeHtml(roleplayStarter.progression)}</li>` : (advancementDetail?.play_note ? `<li><strong>Campaign pace:</strong> ${escapeHtml(advancementDetail.play_note)}</li>` : ''),
                    ...Object.values(configurator.roleplay_reference || {}).map((entry) => entry?.summary ? `<li><strong>${escapeHtml(entry.title || 'Roleplay rule')}:</strong> ${escapeHtml(entry.summary)}</li>` : ''),
                ].filter(Boolean);

                document.getElementById('selected-roleplay-title').textContent = roleplayStarter.title || 'Beginner roleplay help';
                document.getElementById('selected-roleplay-summary').textContent = roleplayStarter.summary
                    || 'Use the sheet choices to sketch one trait, one ideal, one goal, one bond, and one flaw. You do not need a novel.';
                document.getElementById('selected-roleplay-list').innerHTML = [
                    roleplayStarter.watchOut ? `<li><strong>Watch out:</strong> ${escapeHtml(roleplayStarter.watchOut)}</li>` : '',
                    `<li><strong>Trait:</strong> ${escapeHtml(personalityTraitsValue || roleplayStarter.trait || configurator.roleplay_field_help?.personality_traits || '')}</li>`,
                    `<li><strong>Ideal:</strong> ${escapeHtml(idealsValue || roleplayStarter.ideal || configurator.roleplay_field_help?.ideals || '')}</li>`,
                    `<li><strong>Goal:</strong> ${escapeHtml(goalsValue || roleplayStarter.goal || configurator.roleplay_field_help?.goals || '')}</li>`,
                    `<li><strong>Bond:</strong> ${escapeHtml(bondsValue || roleplayStarter.bond || configurator.roleplay_field_help?.bonds || '')}</li>`,
                    `<li><strong>Flaw:</strong> ${escapeHtml(flawsValue || roleplayStarter.flaw || configurator.roleplay_field_help?.flaws || '')}</li>`,
                ].filter(Boolean).join('');
                document.getElementById('selected-roleplay-notes-title').textContent = 'Table roleplay notes';
                document.getElementById('selected-roleplay-notes-summary').textContent = roleplayNotes.length
                    ? 'Campaign pace and social-scene reminders sit here so the starter prompts can stay short.'
                    : 'Campaign pace and social-scene reminders will appear here once the build gives the page something to work with.';
                document.getElementById('selected-roleplay-notes-list').innerHTML = roleplayNotes.length
                    ? roleplayNotes.join('')
                    : '<li>Pick an advancement method or use the roleplay helper to surface broader table notes.</li>';

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

                syncSelectTitle(classSelect);
                syncSelectTitle(advancementMethodSelect);
                syncSelectTitle(subclassSelect);
                syncSelectTitle(backgroundSelect);
                syncSelectTitle(speciesSelect);
                syncSelectTitle(originFeatSelect);
                syncSelectTitle(alignmentSelect);
            }

            // Developer context: Randomint updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function randomInt(min, max) {
                return Math.floor(Math.random() * (max - min + 1)) + min;
            }

            // Developer context: Randomchoice updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function randomChoice(items) {
                return items.length ? items[randomInt(0, items.length - 1)] : null;
            }

            // Developer context: Randomuniquechoices updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function randomUniqueChoices(items, count) {
                const pool = [...items];
                const picks = [];

                while (pool.length && picks.length < count) {
                    const index = randomInt(0, pool.length - 1);
                    picks.push(pool.splice(index, 1)[0]);
                }

                return picks;
            }

            // Developer context: Splitsuggestionpool updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function splitSuggestionPool(value) {
                return String(value ?? '')
                    .replaceAll('...', '')
                    .split(',')
                    .map((entry) => entry.replace(/^or\s+/i, '').trim())
                    .filter(Boolean);
            }

            // Developer context: Randomfromsuggestion updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function randomFromSuggestion(value, fallback = '') {
                const pool = splitSuggestionPool(value);
                return randomChoice(pool) || fallback;
            }

            // Developer context: Localabilityscore updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function localAbilityScore() {
                const rolls = Array.from({ length: 4 }, () => randomInt(1, 6)).sort((a, b) => a - b);
                rolls.shift();
                return rolls.reduce((total, roll) => total + roll, 0);
            }

            // Developer context: Randomlanguagesselection updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function randomLanguagesSelection() {
                const allLanguages = Object.keys(configurator.language_details || {});
                const nonCommon = allLanguages.filter((language) => language !== 'Common');
                const picks = allLanguages.includes('Common') ? ['Common'] : [];
                const extras = randomUniqueChoices(nonCommon, Math.min(nonCommon.length, randomInt(1, 2)));
                const selection = [...picks, ...extras];

                return selection.length ? selection : randomUniqueChoices(allLanguages, 1);
            }

            // Developer context: Randomskillproficiencies updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function randomSkillProficiencies(classValue) {
                const options = classSkillOptions(classValue);
                const choiceCount = classSkillChoiceCount(classValue) || 2;
                return randomUniqueChoices(options, Math.min(options.length, Math.max(1, choiceCount)));
            }

            // Developer context: Randomskillexpertise updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function randomSkillExpertise(classValue, levelValue, proficiencies) {
                if (! Array.isArray(proficiencies) || ! proficiencies.length) return [];

                if (classValue === 'Rogue') {
                    return randomUniqueChoices(proficiencies, Math.min(proficiencies.length, 2));
                }

                if (classValue === 'Bard' && Number(levelValue) >= 2) {
                    return randomUniqueChoices(proficiencies, 2);
                }

                return [];
            }

            // Developer context: Randomcharactername updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function randomCharacterName(species) {
                const speciesProfile = configurator.form_placeholder_profiles?.species?.[species] || {};
                return randomFromSuggestion(speciesProfile.name, `${species} Wanderer`);
            }

            // Developer context: Randomappearanceprofile updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
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

            // Developer context: Fillrandomcharacter updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function fillRandomCharacter() {
                const classOptions = Object.keys(configurator.class_details || {});
                const speciesOptions = Object.keys(configurator.species_details || {});
                const backgroundOptions = Object.keys(configurator.background_details || {});
                const alignmentOptions = Object.keys(configurator.alignment_details || {});
                const originFeatOptions = Object.keys(configurator.origin_feat_details || {});
                const advancementOptions = configurator.advancement_methods || [];

                const classValue = randomChoice(classOptions);
                const speciesValue = randomChoice(speciesOptions);
                const backgroundValue = randomChoice(backgroundOptions);
                const alignmentValue = randomChoice(alignmentOptions);
                const originFeatValue = randomChoice(originFeatOptions);
                const advancementMethodValue = randomChoice(advancementOptions);
                const subclassValue = randomChoice(configurator.class_details?.[classValue]?.subclasses || []);
                const languagesValue = randomLanguagesSelection();
                const skillProficiencyValues = randomSkillProficiencies(classValue);
                const roleplayProfile = configurator.alignment_roleplay?.[alignmentValue] || {};
                const appearance = randomAppearanceProfile(speciesValue);
                const levelValue = randomInt(1, 20);
                const skillExpertiseValues = randomSkillExpertise(classValue, levelValue, skillProficiencyValues);
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
                advancementMethodSelect.value = advancementMethodValue || '';
                nameInput.value = nameValue || '';

                languageInputs.forEach((input) => {
                    input.checked = languagesValue.includes(input.value);
                });
                skillProficiencyInputs.forEach((input) => {
                    input.checked = skillProficiencyValues.includes(input.value);
                });
                syncSkillTrainingNotes();
                skillExpertiseInputs.forEach((input) => {
                    input.checked = skillExpertiseValues.includes(input.value);
                });

                renderDiceStatsResult(generatedStats, { populateForm: true });

                personalityTraitsInput.value = roleplayProfile.starter_trait || personalityTraitsInput.placeholder;
                idealsInput.value = roleplayProfile.starter_ideal || idealsInput.placeholder;
                goalsInput.value = combinedRoleplayStarterPackage().goal || goalsInput.placeholder;
                bondsInput.value = roleplayProfile.starter_bond || bondsInput.placeholder;
                flawsInput.value = roleplayProfile.starter_flaw || flawsInput.placeholder;
                ageInput.value = appearance.age;
                heightInput.value = appearance.height;
                weightInput.value = appearance.weight;
                eyesInput.value = appearance.eyes;
                hairInput.value = appearance.hair;
                skinInput.value = appearance.skin;
                notesInput.value = `${nameValue} is a level ${levelValue} ${subclassValue} ${classValue} with a ${backgroundValue.toLowerCase()} background. The table uses ${String(advancementMethodValue || 'milestone').toLowerCase()} leveling. Speaks ${languagesValue.join(', ')}. Their current goal is ${String(goalsInput.value || 'still taking shape').toLowerCase()}. Ask the DM how their ${backgroundTheme.toLowerCase()} first tied them to the party.`;

                renderSelectionReference();
                notice(formNotice, 'Random character generated. Review anything you want, then save the sheet.', 'success');
                scheduleLocalDraftSave();
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

            // Developer context: Resetform updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function resetForm() {
                form.reset();
                document.getElementById('level').value = 1;
                populateSubclassOptions('');
                renderSelectionReference();
                clearNotice(formNotice);
                scheduleLocalDraftSave();
            }

            // Developer context: Resetdicetray updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function resetDiceTray() {
                diceForm.reset();
                renderDiceResultCard('Ready to roll', ['Pick any die, use a custom expression, or roll full ability scores from here.']);
                clearNotice(diceNotice);
                setLatestRoll('Ready');
                scheduleLocalDraftSave();
            }

            // Developer context: Payload updates one piece of browser-side state or UI; keep it in sync with the handlers that call it.
            // Clear explanation: This part makes one part of the page react or update.
            function payload() {
                const data = Object.fromEntries(new FormData(form).entries());
                delete data['languages[]'];
                delete data['skill_proficiencies[]'];
                delete data['skill_expertise[]'];
                optionalTextFields.forEach((field) => {
                    data[field] = typeof data[field] === 'string' && data[field].trim() ? data[field].trim() : null;
                });
                data.languages = languageInputs.filter((input) => input.checked).map((input) => input.value);
                data.skill_proficiencies = skillProficiencyInputs.filter((input) => input.checked).map((input) => input.value);
                data.skill_expertise = skillExpertiseInputs.filter((input) => input.checked).map((input) => input.value);
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

                if (! skillProficiencyInputs.some((input) => input.checked)) {
                    notice(formNotice, 'Choose at least one skill proficiency before saving the character.', 'error');
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
                        throw new Error(firstErrorMessage(data, 'The character could not be saved.'));
                    }

                    notice(formNotice, 'Character saved successfully.', 'success');
                    resetForm();
                    removeLocalDraft(localDraftKeys.builder);
                    await loadCharacterCount();
                } catch (error) {
                    notice(formNotice, error.message || 'The character could not be saved.', 'error');
                }
            }

            async function sendWizardMessage(message, options = {}) {
                const { echoUser = true, reset = false } = options;

                if (reset) {
                    wizardState = {};
                    wizardMessages = [];
                    lastWizardActions = [];
                    lastWizardSnapshot = null;
                    wizardLog.innerHTML = '';
                    renderWizardActions([]);
                    renderWizardSummary(null);
                    removeLocalDraft(localDraftKeys.wizard);
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
                        throw new Error(firstErrorMessage(data, 'The rules wizard could not respond.'));
                    }

                    wizardState = data.state ?? {};
                    appendWizardMessage('bot', data.reply ?? 'No response.');
                    renderWizardActions(data.quick_actions ?? []);
                    renderWizardSummary(data.snapshot ?? null);

                    if (/^(roll|roll initiative|roll death save)/i.test(message || '')) {
                        setLatestRoll((data.reply ?? 'Rolled').split('\n')[0]);
                    }

                    scheduleLocalDraftSave();
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
            form.addEventListener('input', scheduleLocalDraftSave);
            form.addEventListener('change', scheduleLocalDraftSave);
            diceForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const expression = diceExpressionInput.value.trim();
                if (! expression) return;
                rollDiceExpression(expression, diceModeSelect.value);
            });
            diceForm.addEventListener('input', scheduleLocalDraftSave);
            diceForm.addEventListener('change', scheduleLocalDraftSave);
            wizardForm.addEventListener('submit', (event) => {
                event.preventDefault();
                const message = wizardInput.value.trim();
                if (! message) return;
                wizardInput.value = '';
                scheduleLocalDraftSave();
                sendWizardMessage(message);
            });
            wizardInput.addEventListener('input', scheduleLocalDraftSave);
            compendiumSectionSelect.addEventListener('change', renderCompendium);
            compendiumSearchInput.addEventListener('input', renderCompendium);
            diceButtons.addEventListener('click', (event) => {
                const button = event.target.closest('[data-dice-expression]');
                if (! button) return;
                rollDiceExpression(button.dataset.diceExpression, button.dataset.diceMode || '');
            });
            wireChoiceHelp(classSelect, 'class');
            wireChoiceHelp(advancementMethodSelect, 'advancement_method');
            wireChoiceHelp(subclassSelect, 'subclass');
            wireChoiceHelp(backgroundSelect, 'background');
            wireChoiceHelp(speciesSelect, 'species');
            wireChoiceHelp(originFeatSelect, 'origin_feat');
            wireChoiceHelp(alignmentSelect, 'alignment');
            statFields.forEach(wireAbilityHelp);

            previewEl.addEventListener('mouseover', (event) => {
                const card = event.target.closest('[data-preview-field]');
                if (! card || card.contains(event.relatedTarget)) return;
                const field = card.dataset.previewField;
                showHoverHelp(card, abilityFieldLabel(field), abilityHoverDescription(field));
            });
            previewEl.addEventListener('mouseout', (event) => {
                const card = event.target.closest('[data-preview-field]');
                if (card && ! card.contains(event.relatedTarget)) hideHoverHelp();
            });
            previewEl.addEventListener('focusin', (event) => {
                const card = event.target.closest('[data-preview-field]');
                if (! card) return;
                const field = card.dataset.previewField;
                showHoverHelp(card, abilityFieldLabel(field), abilityHoverDescription(field));
            });
            previewEl.addEventListener('focusout', hideHoverHelp);

            nameInput.addEventListener('input', renderSelectionReference);
            classSelect.addEventListener('change', () => {
                populateSubclassOptions(classSelect.value);
                renderSelectionReference();
            });
            advancementMethodSelect.addEventListener('change', renderSelectionReference);
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
                    showHoverHelp(chip || input, input.value, description || 'This language summary has not been filled in yet.');
                };

                chip?.addEventListener('mouseenter', show);
                chip?.addEventListener('mouseleave', hideHoverHelp);
                chip?.addEventListener('focusin', show);
                chip?.addEventListener('focusout', hideHoverHelp);
                input.removeAttribute('title');
            });
            skillProficiencyInputs.forEach((input) => {
                const chip = input.closest('.check-chip');
                const show = () => {
                    const description = formChoiceDescription('skill', input.value);
                    showHoverHelp(chip || input, input.value, description || 'This skill summary has not been filled in yet.');
                };

                input.addEventListener('change', () => {
                    syncSkillTrainingNotes();
                    renderSelectionReference();
                });
                chip?.addEventListener('mouseenter', show);
                chip?.addEventListener('mouseleave', hideHoverHelp);
                chip?.addEventListener('focusin', show);
                chip?.addEventListener('focusout', hideHoverHelp);
                input.removeAttribute('title');
            });
            skillExpertiseInputs.forEach((input) => {
                const chip = input.closest('.check-chip');
                const show = () => {
                    const description = skillExpertiseDescription(input.value) || 'This expertise summary has not been filled in yet.';
                    showHoverHelp(chip || input, `${input.value} expertise`, description);
                };

                input.addEventListener('change', renderSelectionReference);
                chip?.addEventListener('mouseenter', show);
                chip?.addEventListener('mouseleave', hideHoverHelp);
                chip?.addEventListener('focusin', show);
                chip?.addEventListener('focusout', hideHoverHelp);
                input.removeAttribute('title');
            });
            levelInput.addEventListener('input', renderSelectionReference);
            [personalityTraitsInput, idealsInput, goalsInput, bondsInput, flawsInput, ageInput, heightInput, weightInput, eyesInput, hairInput, skinInput].forEach((input) => {
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
            window.addEventListener('resize', () => {
                if (currentHoverAnchor) positionHoverTooltip(currentHoverAnchor);
            });
            window.addEventListener('scroll', () => {
                if (currentHoverAnchor) positionHoverTooltip(currentHoverAnchor);
            }, true);
            window.addEventListener('beforeunload', persistLocalDrafts);

            populateSubclassOptions('');
            filterTryAsking();
            const restoredSections = [];
            const restoredBuilderDraft = applyBuilderDraft(readLocalDraft(localDraftKeys.builder));
            if (! restoredBuilderDraft) {
                renderSelectionReference();
            } else {
                restoredSections.push('builder');
            }

            const restoredDiceDraft = restoreDiceDraft();
            if (! restoredDiceDraft) {
                resetDiceTray();
            } else {
                restoredSections.push('dice tray');
            }

            if (restoreLibraryDraft()) {
                restoredSections.push('library');
            }
            loadCharacterCount();
            const restoredWizardDraft = restoreWizardDraft();
            if (! restoredWizardDraft) {
                sendWizardMessage('', { echoUser: false });
            } else {
                restoredSections.push('wizard');
            }

            showDraftRestoreNotice(restoredSections);
            window.setInterval(persistLocalDrafts, 10000);
        </script>
    </body>
</html>
