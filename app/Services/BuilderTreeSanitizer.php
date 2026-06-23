<?php

namespace App\Services;

use App\Support\InlineContentSanitizer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BuilderTreeSanitizer
{
    public function __construct(
        private readonly PageBlockService $pageBlockService,
    ) {}

    /** @param array<int, mixed> $nodes
     * @return array<int, array<string, mixed>>
     */
    public function sanitizeTree(array $nodes, string $errorKey = 'blocks'): array
    {
        $nodeCount = 0;
        $sanitized = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                throw ValidationException::withMessages([$errorKey => 'Every tree item must be a block object.']);
            }
            $sanitized[] = $this->sanitizeNodeInternal($node, $nodeCount, 0, null, $errorKey);
        }

        return $sanitized;
    }

    /** @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    public function sanitizeNode(array $node, string $errorKey = 'blocks'): array
    {
        $nodeCount = 0;

        return $this->sanitizeNodeInternal($node, $nodeCount, 0, null, $errorKey);
    }

    /** @param array<string, mixed> $node
     * @return array<string, mixed>
     */
    private function sanitizeNodeInternal(
        array $node,
        int &$nodeCount,
        int $depth,
        ?string $parentType,
        string $errorKey,
    ): array {
        if ($depth > 5 || ++$nodeCount > 200) {
            throw ValidationException::withMessages([$errorKey => 'A builder tree may contain at most 200 blocks and five nesting levels.']);
        }

        $validTypes = array_keys((array) config('blocks', []));
        $validated = Validator::make($node, [
            'block_type' => ['required', 'string', Rule::in($validTypes)],
            'label' => ['nullable', 'string', 'max:255'],
            'data' => ['nullable', 'array'],
            'is_visible' => ['nullable', 'boolean'],
            'children' => ['nullable', 'array'],
        ])->validate();

        $blockType = (string) $validated['block_type'];
        if ($parentType === 'columns' && $blockType !== 'group') {
            throw ValidationException::withMessages([$errorKey => 'Columns may contain Group blocks only.']);
        }

        $children = $validated['children'] ?? [];
        if (! is_array($children)) {
            $children = [];
        }
        if ($children !== [] && ! in_array($blockType, ['group', 'columns'], true)) {
            throw ValidationException::withMessages([$errorKey => 'Only Group and Columns blocks may contain children.']);
        }

        $sanitizedChildren = [];
        foreach ($children as $child) {
            if (! is_array($child)) {
                throw ValidationException::withMessages([$errorKey => 'Every child must be a block object.']);
            }
            $sanitizedChildren[] = $this->sanitizeNodeInternal($child, $nodeCount, $depth + 1, $blockType, $errorKey);
        }

        $rawData = $validated['data'] ?? [];
        $data = $this->pageBlockService->validateAndSanitizeData(
            $blockType,
            is_array($rawData) ? $rawData : [],
        );
        $label = InlineContentSanitizer::plaintext((string) ($validated['label'] ?? ''));

        return [
            'block_type' => $blockType,
            'label' => $label !== '' ? $label : (string) config("blocks.{$blockType}.label", Str::headline($blockType)),
            'data' => $data,
            'is_visible' => (bool) ($validated['is_visible'] ?? true),
            'children' => $sanitizedChildren,
        ];
    }
}
