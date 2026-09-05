<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bucket;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the fixed priority stack a contiguous 1..n list.
 *
 * Slotting a bucket in at position 2 has to push whatever sat at 2 (and everything
 * below it) down a place, otherwise two buckets share a slot and the deposit
 * waterfall funds them in an arbitrary order.
 */
class BucketPriorityService
{
    /**
     * Put a saved bucket at the position the user asked for, shifting its neighbours
     * to make room. A null position appends to the end of the stack. Buckets that are
     * not fixed hold no slot at all.
     */
    public function place(Bucket $bucket, ?int $desired): void
    {
        if ($bucket->type !== Bucket::TYPE_FIXED) {
            $this->write($bucket, null);
            $this->resequence();

            return;
        }

        $others = $this->stack()->reject(fn (Bucket $other) => $other->is($bucket))->values();

        // Clamp into the stack: one past the end is "last", anything lower is "first".
        $position = $desired === null
            ? $others->count() + 1
            : max(1, min($desired, $others->count() + 1));

        $ordered = $others->all();
        array_splice($ordered, $position - 1, 0, [$bucket]);

        $this->persist($ordered);
    }

    /**
     * Close any gaps left by a deletion or a bucket changing type.
     */
    public function resequence(): void
    {
        $this->persist($this->stack()->all());
    }

    /**
     * @return \Illuminate\Support\Collection<int, Bucket>
     */
    private function stack(): \Illuminate\Support\Collection
    {
        return Bucket::where('type', Bucket::TYPE_FIXED)
            ->orderByRaw('priority_order IS NULL, priority_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<int, Bucket>  $ordered
     */
    private function persist(array $ordered): void
    {
        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $index => $bucket) {
                $slot = $index + 1;

                if ($bucket->priority_order !== $slot) {
                    $this->write($bucket, $slot);
                }
            }
        });
    }

    /**
     * Written straight through so reordering does not bump `updated_at`, matching
     * the drag-and-drop reorder endpoint.
     */
    private function write(Bucket $bucket, ?int $slot): void
    {
        if ($bucket->priority_order === $slot) {
            return;
        }

        DB::table('buckets')->where('id', $bucket->id)->update(['priority_order' => $slot]);
        $bucket->setAttribute('priority_order', $slot)->syncOriginalAttribute('priority_order');
    }
}
