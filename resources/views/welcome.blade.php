<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>D&amp;D Karakterbeheer</title>
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

            .hero-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.75rem;
                margin-top: 1rem;
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

            .panel-intro {
                margin: 0 0 1rem;
                color: var(--muted);
                line-height: 1.6;
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

            .button-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 999px;
                padding: 0.8rem 1rem;
                font: inherit;
                text-decoration: none;
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

            .modal-backdrop {
                position: fixed;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background: rgba(30, 27, 22, 0.56);
            }

            .modal-backdrop.show {
                display: flex;
            }

            .modal {
                width: min(720px, 100%);
                max-height: calc(100vh - 2rem);
                overflow-y: auto;
                padding: 1.25rem;
                border: 1px solid var(--line);
                border-radius: 18px;
                background: var(--card);
                box-shadow: 0 22px 60px rgba(30, 27, 22, 0.2);
            }

            .modal-header {
                display: flex;
                align-items: start;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 1rem;
            }

            .modal-header h2 {
                margin: 0 0 0.35rem;
            }

            .modal-header p {
                margin: 0;
                color: var(--muted);
                line-height: 1.6;
            }

            .modal-close {
                min-width: 44px;
                padding-inline: 0;
                font-size: 1.25rem;
                line-height: 1;
            }

            body.modal-open {
                overflow: hidden;
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
                <h1>D&amp;D Karakterbeheer</h1>
                <p>Dit is een eenvoudige schoolapp met een eigen Laravel API.</p>
                <p>Je kunt hier karakters bekijken, toevoegen, aanpassen en verwijderen.</p>
                <div class="hero-actions">
                    <a class="button-link btn-secondary" href="{{ route('api.overview') }}">Bekijk API-overzicht</a>
                </div>
            </section>

            <div class="layout">
                <section class="panel">
                    <h2>Nieuw karakter</h2>
                    <p class="panel-intro">Gebruik dit formulier om een nieuw karakter toe te voegen. Bewerken gaat via de knop bij een bestaand karakter.</p>
                    <div class="notice" id="form-notice"></div>

                    <form id="character-form">
                        <div class="field">
                            <label for="name">Naam</label>
                            <input id="name" name="name" type="text" maxlength="100" required>
                        </div>

                        <div class="two-cols">
                            <div class="field">
                            <label for="species">Soort</label>
                                <input id="species" name="species" type="text" maxlength="50" required>
                            </div>

                            <div class="field">
                            <label for="class">Klasse</label>
                                <input id="class" name="class" type="text" maxlength="50" required>
                            </div>
                        </div>

                        <div class="two-cols">
                            <div class="field">
                                <label for="subclass">Subklasse</label>
                                <input id="subclass" name="subclass" type="text" maxlength="50">
                            </div>

                            <div class="field">
                                <label for="background">Achtergrond</label>
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
                    <h2>Karakters</h2>
                    <div class="summary" id="summary"></div>
                    <div class="notice" id="list-notice"></div>
                    <div class="list" id="character-list"></div>
                </section>
            </div>
        </div>

        <div aria-hidden="true" class="modal-backdrop" id="edit-modal">
            <section aria-labelledby="edit-title" aria-modal="true" class="modal" role="dialog">
                <div class="modal-header">
                    <div>
                        <h2 id="edit-title">Karakter bewerken</h2>
                        <p>Pas hier de gegevens aan van het gekozen karakter.</p>
                    </div>
                    <button aria-label="Sluiten" class="btn-secondary modal-close" id="edit-close" type="button">&times;</button>
                </div>

                <div class="notice" id="edit-notice"></div>

                <form id="edit-form">
                    <input id="edit-id" type="hidden">

                    <div class="field">
                        <label for="edit-name">Naam</label>
                        <input id="edit-name" name="name" type="text" maxlength="100" required>
                    </div>

                    <div class="two-cols">
                        <div class="field">
                            <label for="edit-species">Soort</label>
                            <input id="edit-species" name="species" type="text" maxlength="50" required>
                        </div>

                        <div class="field">
                            <label for="edit-class">Klasse</label>
                            <input id="edit-class" name="class" type="text" maxlength="50" required>
                        </div>
                    </div>

                    <div class="two-cols">
                        <div class="field">
                            <label for="edit-subclass">Subklasse</label>
                            <input id="edit-subclass" name="subclass" type="text" maxlength="50">
                        </div>

                        <div class="field">
                            <label for="edit-background">Achtergrond</label>
                            <input id="edit-background" name="background" type="text" maxlength="50" required>
                        </div>
                    </div>

                    <div class="two-cols">
                        <div class="field">
                            <label for="edit-alignment">Alignment</label>
                            <input id="edit-alignment" name="alignment" type="text" maxlength="30">
                        </div>

                        <div class="field">
                            <label for="edit-level">Level</label>
                            <input id="edit-level" name="level" type="number" min="1" max="20" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="edit-notes">Notities</label>
                        <textarea id="edit-notes" name="notes" maxlength="1000"></textarea>
                    </div>

                    <div class="actions">
                        <button class="btn-primary" type="submit">Wijzigingen opslaan</button>
                        <button class="btn-secondary" id="edit-cancel" type="button">Annuleren</button>
                    </div>
                </form>
            </section>
        </div>

        <script>
            const form = document.getElementById('character-form');
            const formNotice = document.getElementById('form-notice');
            const listNotice = document.getElementById('list-notice');
            const characterList = document.getElementById('character-list');
            const summary = document.getElementById('summary');
            const cancelButton = document.getElementById('cancel-button');
            const editModal = document.getElementById('edit-modal');
            const editForm = document.getElementById('edit-form');
            const editNotice = document.getElementById('edit-notice');
            const editCloseButton = document.getElementById('edit-close');
            const editCancelButton = document.getElementById('edit-cancel');
            const createFields = {
                name: document.getElementById('name'),
                species: document.getElementById('species'),
                class: document.getElementById('class'),
                subclass: document.getElementById('subclass'),
                background: document.getElementById('background'),
                alignment: document.getElementById('alignment'),
                level: document.getElementById('level'),
                notes: document.getElementById('notes'),
            };
            const editFields = {
                id: document.getElementById('edit-id'),
                name: document.getElementById('edit-name'),
                species: document.getElementById('edit-species'),
                class: document.getElementById('edit-class'),
                subclass: document.getElementById('edit-subclass'),
                background: document.getElementById('edit-background'),
                alignment: document.getElementById('edit-alignment'),
                level: document.getElementById('edit-level'),
                notes: document.getElementById('edit-notes'),
            };

            let characters = @json($initialCharacters ?? []);
            const noticeTimers = new WeakMap();

            function clearNoticeTimer(target) {
                const timer = noticeTimers.get(target);

                if (timer) {
                    window.clearTimeout(timer);
                    noticeTimers.delete(target);
                }
            }

            function showNotice(target, message, type, duration = null) {
                clearNoticeTimer(target);
                target.textContent = message;
                target.className = `notice show ${type}`;

                if (Number.isFinite(duration) && duration > 0) {
                    const timer = window.setTimeout(() => {
                        clearNotice(target);
                    }, duration);

                    noticeTimers.set(target, timer);
                }
            }

            function clearNotice(target) {
                clearNoticeTimer(target);
                target.textContent = '';
                target.className = 'notice';
            }

            function resetForm() {
                form.reset();
                createFields.level.value = 1;
                clearNotice(formNotice);
            }

            function resetEditForm() {
                editForm.reset();
                editFields.id.value = '';
                editFields.level.value = 1;
                clearNotice(editNotice);
            }

            function formPayload(fields) {
                return {
                    name: fields.name.value.trim(),
                    species: fields.species.value.trim(),
                    class: fields.class.value.trim(),
                    subclass: fields.subclass.value.trim(),
                    background: fields.background.value.trim(),
                    alignment: fields.alignment.value.trim(),
                    level: Number(fields.level.value),
                    notes: fields.notes.value.trim(),
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

            function openEditModal() {
                editModal.classList.add('show');
                editModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
            }

            function closeEditModal() {
                editModal.classList.remove('show');
                editModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                resetEditForm();
            }

            function fillEditForm(character) {
                editFields.id.value = character.id;
                editFields.name.value = character.name ?? '';
                editFields.species.value = character.species ?? '';
                editFields.class.value = character.class ?? '';
                editFields.subclass.value = character.subclass ?? '';
                editFields.background.value = character.background ?? '';
                editFields.alignment.value = character.alignment ?? '';
                editFields.level.value = character.level ?? 1;
                editFields.notes.value = character.notes ?? '';
                clearNotice(editNotice);
            }

            function renderCharacters() {
                summary.textContent = `${characters.length} karakter(s) gevonden.`;

                if (characters.length === 0) {
                    characterList.innerHTML = '<div class="empty">Er zijn nog geen karakters opgeslagen.</div>';
                    return;
                }

                characterList.innerHTML = characters.map((character) => `
                    <article class="card">
                        <h3>${character.name}</h3>
                        <div class="meta">
                            Soort: ${character.species}<br>
                            Klasse: ${character.class}${character.subclass ? ` (${character.subclass})` : ''}<br>
                            Achtergrond: ${character.background}<br>
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
                    showNotice(listNotice, error.message || 'Laden van karakters mislukt.', 'error');
                }
            }

            async function saveCharacter(event) {
                event.preventDefault();
                clearNotice(formNotice);

                try {
                    const response = await fetch('/api/characters', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formPayload(createFields)),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(firstErrorMessage(data));
                    }

                    showNotice(formNotice, data.message || 'Karakter opgeslagen.', 'success');
                    await loadCharacters();
                    resetForm();
                    showNotice(formNotice, data.message || 'Karakter opgeslagen.', 'success', 10000);
                } catch (error) {
                    showNotice(formNotice, error.message || 'Opslaan mislukt.', 'error');
                }
            }

            async function updateCharacter(event) {
                event.preventDefault();
                clearNotice(editNotice);

                const characterId = editFields.id.value;

                if (!characterId) {
                    showNotice(editNotice, 'Er is geen karakter gekozen om te bewerken.', 'error');
                    return;
                }

                try {
                    const response = await fetch(`/api/characters/${characterId}`, {
                        method: 'PUT',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formPayload(editFields)),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(firstErrorMessage(data));
                    }

                    await loadCharacters();
                    closeEditModal();
                    showNotice(listNotice, data.message || 'Karakter bijgewerkt.', 'success', 10000);
                } catch (error) {
                    showNotice(editNotice, error.message || 'Bijwerken mislukt.', 'error');
                }
            }

            function editCharacter(id) {
                clearNotice(listNotice);

                const character = characters.find((item) => item.id === id);

                if (!character) {
                    showNotice(listNotice, 'Karakter laden mislukt.', 'error');
                    return;
                }

                fillEditForm(character);
                openEditModal();
            }

            async function deleteCharacter(id) {
                if (!window.confirm('Weet je zeker dat je dit karakter wilt verwijderen?')) {
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
                    if (editFields.id.value === String(id)) {
                        closeEditModal();
                    }

                    showNotice(listNotice, data.message || 'Karakter verwijderd.', 'success', 10000);
                } catch (error) {
                    showNotice(listNotice, error.message || 'Verwijderen mislukt.', 'error');
                }
            }

            form.addEventListener('submit', saveCharacter);
            cancelButton.addEventListener('click', resetForm);
            editForm.addEventListener('submit', updateCharacter);
            editCloseButton.addEventListener('click', closeEditModal);
            editCancelButton.addEventListener('click', closeEditModal);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && editModal.classList.contains('show')) {
                    closeEditModal();
                }
            });

            characterList.addEventListener('click', (event) => {
                const editButton = event.target.closest('[data-edit]');
                const deleteButton = event.target.closest('[data-delete]');

                if (editButton) {
                    editCharacter(Number(editButton.dataset.edit));
                }

                if (deleteButton) {
                    deleteCharacter(Number(deleteButton.dataset.delete));
                }
            });

            resetForm();
            renderCharacters();
        </script>
    </body>
</html>
