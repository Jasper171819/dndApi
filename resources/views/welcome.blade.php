<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>D&amp;D Character Beheer</title>
        <style>
            :root {
                --bg: #f4f1ea;
                --card: #ffffff;
                --line: #d8d1c3;
                --text: #1e1b16;
                --muted: #6f675c;
                --accent: #7a4d2a;
                --accent-soft: #efe4d8;
                --danger: #a33a2b;
                --success: #2f6b3b;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: "Segoe UI", Tahoma, sans-serif;
                color: var(--text);
                background: var(--bg);
            }

            .page {
                width: min(1100px, calc(100% - 2rem));
                margin: 0 auto;
                padding: 2rem 0 3rem;
            }

            .hero {
                margin-bottom: 1.5rem;
                padding: 1.5rem;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: var(--card);
            }

            .hero h1 {
                margin: 0 0 0.75rem;
                font-size: clamp(1.9rem, 4vw, 2.8rem);
            }

            .hero p {
                margin: 0.4rem 0;
                color: var(--muted);
                line-height: 1.6;
            }

            .layout {
                display: grid;
                grid-template-columns: minmax(300px, 360px) minmax(0, 1fr);
                gap: 1rem;
                align-items: start;
            }

            .panel {
                padding: 1.25rem;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: var(--card);
            }

            .panel h2 {
                margin: 0 0 0.9rem;
                font-size: 1.3rem;
            }

            .notice {
                display: none;
                margin-bottom: 1rem;
                padding: 0.9rem 1rem;
                border-radius: 12px;
                font-size: 0.95rem;
            }

            .notice.show {
                display: block;
            }

            .notice.success {
                color: #18461f;
                background: #dff1e2;
                border: 1px solid #b9dbbe;
            }

            .notice.error {
                color: #671d13;
                background: #f9dfdb;
                border: 1px solid #efbeb6;
            }

            .field {
                display: grid;
                gap: 0.35rem;
                margin-bottom: 0.9rem;
            }

            .field label {
                font-size: 0.95rem;
                color: var(--muted);
            }

            .field input,
            .field textarea {
                width: 100%;
                padding: 0.75rem 0.85rem;
                border: 1px solid var(--line);
                border-radius: 12px;
                font: inherit;
                color: var(--text);
                background: #fff;
            }

            .field textarea {
                min-height: 120px;
                resize: vertical;
            }

            .two-cols {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.8rem;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            button {
                border: 0;
                border-radius: 999px;
                padding: 0.8rem 1rem;
                font: inherit;
                cursor: pointer;
            }

            .btn-primary {
                color: #fff;
                background: var(--accent);
            }

            .btn-secondary {
                color: var(--text);
                background: var(--accent-soft);
            }

            .btn-danger {
                color: #fff;
                background: var(--danger);
            }

            .summary {
                margin-bottom: 1rem;
                color: var(--muted);
            }

            .list {
                display: grid;
                gap: 1rem;
            }

            .card {
                padding: 1rem;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: #fcfbf8;
            }

            .card h3 {
                margin: 0 0 0.35rem;
                font-size: 1.15rem;
            }

            .meta {
                color: var(--muted);
                font-size: 0.95rem;
                line-height: 1.6;
            }

            .card p {
                margin: 0.85rem 0;
                line-height: 1.6;
            }

            .empty {
                padding: 1.25rem;
                border: 1px dashed var(--line);
                border-radius: 14px;
                color: var(--muted);
                text-align: center;
            }

            @media (max-width: 860px) {
                .layout,
                .two-cols {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    </head>
    <body>
        <div class="page">
            <section class="hero">
                <h1>D&amp;D Character Beheer</h1>
                <p>Dit is een eenvoudige schoolapp met een eigen Laravel API.</p>
                <p>Je kunt hier characters bekijken, toevoegen, aanpassen en verwijderen.</p>
            </section>

            <div class="layout">
                <section class="panel">
                    <h2 id="form-title">Nieuw character</h2>
                    <div class="notice" id="form-notice"></div>

                    <form id="character-form">
                        <input id="character-id" type="hidden">

                        <div class="field">
                            <label for="name">Naam</label>
                            <input id="name" name="name" type="text" maxlength="100" required>
                        </div>

                        <div class="two-cols">
                            <div class="field">
                                <label for="species">Species</label>
                                <input id="species" name="species" type="text" maxlength="50" required>
                            </div>

                            <div class="field">
                                <label for="class">Class</label>
                                <input id="class" name="class" type="text" maxlength="50" required>
                            </div>
                        </div>

                        <div class="two-cols">
                            <div class="field">
                                <label for="subclass">Subclass</label>
                                <input id="subclass" name="subclass" type="text" maxlength="50">
                            </div>

                            <div class="field">
                                <label for="background">Background</label>
                                <input id="background" name="background" type="text" maxlength="50" required>
                            </div>
                        </div>

                        <div class="two-cols">
                            <div class="field">
                                <label for="alignment">Alignment</label>
                                <input id="alignment" name="alignment" type="text" maxlength="30">
                            </div>

                            <div class="field">
                                <label for="level">Level</label>
                                <input id="level" name="level" type="number" min="1" max="20" value="1" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="notes">Notities</label>
                            <textarea id="notes" name="notes" maxlength="1000"></textarea>
                        </div>

                        <div class="actions">
                            <button class="btn-primary" id="save-button" type="submit">Opslaan</button>
                            <button class="btn-secondary" id="cancel-button" type="button">Annuleren</button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <h2>Characters</h2>
                    <div class="summary" id="summary">Laden...</div>
                    <div class="notice" id="list-notice"></div>
                    <div class="list" id="character-list"></div>
                </section>
            </div>
        </div>

        <script>
            const form = document.getElementById('character-form');
            const formTitle = document.getElementById('form-title');
            const formNotice = document.getElementById('form-notice');
            const listNotice = document.getElementById('list-notice');
            const characterList = document.getElementById('character-list');
            const summary = document.getElementById('summary');
            const cancelButton = document.getElementById('cancel-button');
            const saveButton = document.getElementById('save-button');
            const fields = {
                id: document.getElementById('character-id'),
                name: document.getElementById('name'),
                species: document.getElementById('species'),
                class: document.getElementById('class'),
                subclass: document.getElementById('subclass'),
                background: document.getElementById('background'),
                alignment: document.getElementById('alignment'),
                level: document.getElementById('level'),
                notes: document.getElementById('notes'),
            };

            let characters = [];

            function showNotice(target, message, type) {
                target.textContent = message;
                target.className = `notice show ${type}`;
            }

            function clearNotice(target) {
                target.textContent = '';
                target.className = 'notice';
            }

            function resetForm() {
                form.reset();
                fields.id.value = '';
                fields.level.value = 1;
                formTitle.textContent = 'Nieuw character';
                saveButton.textContent = 'Opslaan';
                clearNotice(formNotice);
            }

            function formPayload() {
                return {
                    name: fields.name.value,
                    species: fields.species.value,
                    class: fields.class.value,
                    subclass: fields.subclass.value,
                    background: fields.background.value,
                    alignment: fields.alignment.value,
                    level: Number(fields.level.value),
                    notes: fields.notes.value,
                };
            }

            function firstErrorMessage(data) {
                if (!data || typeof data !== 'object') {
                    return 'Er ging iets mis.';
                }

                if (data.errors && typeof data.errors === 'object') {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
                        return data.errors[firstKey][0];
                    }
                }

                return data.message || 'Er ging iets mis.';
            }

            function fillForm(character) {
                fields.id.value = character.id;
                fields.name.value = character.name ?? '';
                fields.species.value = character.species ?? '';
                fields.class.value = character.class ?? '';
                fields.subclass.value = character.subclass ?? '';
                fields.background.value = character.background ?? '';
                fields.alignment.value = character.alignment ?? '';
                fields.level.value = character.level ?? 1;
                fields.notes.value = character.notes ?? '';
                formTitle.textContent = `Character bewerken: ${character.name}`;
                saveButton.textContent = 'Bijwerken';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                clearNotice(formNotice);
            }

            function renderCharacters() {
                summary.textContent = `${characters.length} character(s) gevonden.`;

                if (characters.length === 0) {
                    characterList.innerHTML = '<div class="empty">Er zijn nog geen characters opgeslagen.</div>';
                    return;
                }

                characterList.innerHTML = characters.map((character) => `
                    <article class="card">
                        <h3>${character.name}</h3>
                        <div class="meta">
                            Species: ${character.species}<br>
                            Class: ${character.class}${character.subclass ? ` (${character.subclass})` : ''}<br>
                            Background: ${character.background}<br>
                            Alignment: ${character.alignment || 'Niet ingevuld'}<br>
                            Level: ${character.level}
                        </div>
                        <p>${character.notes || 'Geen notities.'}</p>
                        <div class="actions">
                            <button class="btn-secondary" type="button" data-edit="${character.id}">Bewerken</button>
                            <button class="btn-danger" type="button" data-delete="${character.id}">Verwijderen</button>
                        </div>
                    </article>
                `).join('');
            }

            async function loadCharacters() {
                try {
                    clearNotice(listNotice);
                    const response = await fetch('/api/characters', {
                        headers: { Accept: 'application/json' },
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(firstErrorMessage(data));
                    }

                    characters = Array.isArray(data.data) ? data.data : [];
                    renderCharacters();
                } catch (error) {
                    characters = [];
                    renderCharacters();
                    showNotice(listNotice, error.message || 'Laden van characters mislukt.', 'error');
                }
            }

            async function saveCharacter(event) {
                event.preventDefault();
                clearNotice(formNotice);

                const characterId = fields.id.value;
                const method = characterId ? 'PUT' : 'POST';
                const url = characterId ? `/api/characters/${characterId}` : '/api/characters';

                try {
                    const response = await fetch(url, {
                        method,
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formPayload()),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(firstErrorMessage(data));
                    }

                    showNotice(formNotice, data.message || 'Character opgeslagen.', 'success');
                    await loadCharacters();
                    resetForm();
                    showNotice(formNotice, data.message || 'Character opgeslagen.', 'success');
                } catch (error) {
                    showNotice(formNotice, error.message || 'Opslaan mislukt.', 'error');
                }
            }

            async function deleteCharacter(id) {
                if (!window.confirm('Weet je zeker dat je dit character wilt verwijderen?')) {
                    return;
                }

                try {
                    clearNotice(listNotice);
                    const response = await fetch(`/api/characters/${id}`, {
                        method: 'DELETE',
                        headers: { Accept: 'application/json' },
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(firstErrorMessage(data));
                    }

                    await loadCharacters();

                    if (fields.id.value === String(id)) {
                        resetForm();
                    }

                    showNotice(listNotice, data.message || 'Character verwijderd.', 'success');
                } catch (error) {
                    showNotice(listNotice, error.message || 'Verwijderen mislukt.', 'error');
                }
            }

            form.addEventListener('submit', saveCharacter);
            cancelButton.addEventListener('click', resetForm);

            characterList.addEventListener('click', (event) => {
                const editButton = event.target.closest('[data-edit]');
                const deleteButton = event.target.closest('[data-delete]');

                if (editButton) {
                    const character = characters.find((item) => item.id === Number(editButton.dataset.edit));
                    if (character) {
                        fillForm(character);
                    }
                }

                if (deleteButton) {
                    deleteCharacter(Number(deleteButton.dataset.delete));
                }
            });

            resetForm();
            loadCharacters();
        </script>
    </body>
</html>
