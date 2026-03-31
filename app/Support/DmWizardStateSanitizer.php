<?php
// Developer context: This helper cleans the nested DM wizard state before the service uses or saves it; it relies on the DM record validator for draft data and the plain-text normalizer for simple state fields.
// Clear explanation: This file cleans the DM wizard's saved state so broken or unexpected data does not corrupt the wizard flow.

namespace App\Support;

class DmWizardStateSanitizer
{
    // Developer context: Laravel injects the shared DM record validator and text normalizer so DM wizard cleanup follows the same rules as direct DM record saves.
    // Clear explanation: This sets up the helpers that keep the DM wizard state clean and consistent.
    public function __construct(
        private readonly DmRecordDataValidator $dmRecords,
        private readonly PlainTextNormalizer $plainText,
    ) {}

    // Developer context: This method whitelists the DM wizard top-level keys and sanitizes the draft record plus the lightweight linkage metadata used by the page.
    // Clear explanation: This method filters the DM wizard state down to the safe fields and cleans each part before the wizard keeps using it.
    public function sanitize(array $state): array
    {
        $draftRecord = $this->sanitizeDraftRecord(
            is_array($state['draft_record'] ?? null) ? $state['draft_record'] : [],
        );

        $flowKind = $this->normalizeFlowKind($state['flow_kind'] ?? null)
            ?? ($draftRecord['kind'] ?? null);

        if ($flowKind !== null && ($draftRecord['kind'] ?? null) === null) {
            $draftRecord['kind'] = $flowKind;
            $draftRecord['payload'] = $this->dmRecords->starterRecord($flowKind)['payload'];
        }

        return [
            'flow_kind' => $flowKind,
            'pending_field' => $this->normalizePendingField($state['pending_field'] ?? null),
            'skipped_optional_fields' => array_values(array_filter(array_map(
                fn (mixed $value): ?string => $this->normalizePendingField($value),
                is_array($state['skipped_optional_fields'] ?? null) ? $state['skipped_optional_fields'] : [],
            ))),
            'draft_record' => $draftRecord,
            'page_linkage' => $this->sanitizePageLinkage(
                is_array($state['page_linkage'] ?? null) ? $state['page_linkage'] : [],
            ),
        ];
    }

    // Developer context: This helper keeps the persisted record ID if present while reusing the main DM record validator for the rest of the draft payload.
    // Clear explanation: This cleans the current DM record draft while still remembering which saved record it came from.
    private function sanitizeDraftRecord(array $draftRecord): array
    {
        $sanitized = $this->dmRecords->normalizeDraft($draftRecord);
        $sanitized['id'] = $this->normalizeInteger($draftRecord['id'] ?? null);

        return $sanitized;
    }

    // Developer context: This helper sanitizes the small state block that tells the page whether the current draft can patch the session board or encounter tracker.
    // Clear explanation: This cleans the small set of flags the DM page uses to connect wizard output to the live tools.
    private function sanitizePageLinkage(array $pageLinkage): array
    {
        return [
            'last_saved_record_id' => $this->normalizeInteger($pageLinkage['last_saved_record_id'] ?? null),
            'session_patch_ready' => filter_var($pageLinkage['session_patch_ready'] ?? false, FILTER_VALIDATE_BOOL),
            'encounter_patch_ready' => filter_var($pageLinkage['encounter_patch_ready'] ?? false, FILTER_VALIDATE_BOOL),
            'npc_patch_ready' => filter_var($pageLinkage['npc_patch_ready'] ?? false, FILTER_VALIDATE_BOOL),
        ];
    }

    // Developer context: This helper normalizes the record kind the wizard is currently building.
    // Clear explanation: This cleans the wizard's current record type, like NPC or scene.
    private function normalizeFlowKind(mixed $value): ?string
    {
        $normalized = $this->plainText->normalize($value);

        if (! is_string($normalized)) {
            return null;
        }

        $normalized = strtolower($normalized);

        return in_array($normalized, $this->dmRecords->knownKinds(), true) ? $normalized : null;
    }

    // Developer context: This helper cleans the current pending-field marker without trying to validate it against every kind-specific field name.
    // Clear explanation: This cleans the name of the wizard step it is currently waiting for.
    private function normalizePendingField(mixed $value): ?string
    {
        $normalized = $this->plainText->normalize($value);

        return is_string($normalized) ? strtolower(str_replace(' ', '_', $normalized)) : null;
    }

    // Developer context: This helper accepts only valid whole-number IDs and drops malformed numeric values.
    // Clear explanation: This turns an ID into a real number only when it is valid.
    private function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = trim((string) $value);

        if (preg_match('/^\d+$/', $normalized) !== 1) {
            return null;
        }

        return (int) $normalized;
    }
}
