<?php

namespace App\Console\Commands;

use App\Models\Signal;
use App\Models\SignalEvent;
use App\Services\Signals\SignalEvaluator;
use Illuminate\Console\Command;

class CheckSignals extends Command
{
    protected $signature = 'signals:check';

    protected $description = 'Evaluate all active signals, firing new signal_events and auto-resolving ones that no longer apply.';

    public function handle(SignalEvaluator $evaluator): int
    {
        $signals = Signal::where('active', true)->get();
        $opened = 0;
        $resolved = 0;

        foreach ($signals as $signal) {
            $matches = $evaluator->evaluate($signal);
            $currentKeys = $matches->map(fn ($m) => SignalEvaluator::dedupeKey($m['ncr_id'], $m['supplier_id'], $m['context']))->all();

            $openEvents = $signal->openEvents()->get();
            $openByKey = $openEvents->keyBy(fn (SignalEvent $e) => SignalEvaluator::dedupeKey($e->ncr_id, $e->supplier_id, $e->context));

            foreach ($matches as $match) {
                $key = SignalEvaluator::dedupeKey($match['ncr_id'], $match['supplier_id'], $match['context']);

                if (! $openByKey->has($key)) {
                    SignalEvent::create([
                        'signal_id' => $signal->id,
                        'ncr_id' => $match['ncr_id'],
                        'supplier_id' => $match['supplier_id'],
                        'context' => $match['context'],
                        'triggered_at' => now(),
                    ]);
                    $opened++;
                }
            }

            foreach ($openEvents as $event) {
                $key = SignalEvaluator::dedupeKey($event->ncr_id, $event->supplier_id, $event->context);

                if (! in_array($key, $currentKeys, true)) {
                    $event->update([
                        'resolved_at' => now(),
                        'note' => trim(($event->note ? $event->note.' ' : '').'Auto-resolved: condition no longer applies.'),
                    ]);
                    $resolved++;
                }
            }
        }

        $this->info("Signals check complete: {$opened} event(s) opened, {$resolved} auto-resolved.");

        return self::SUCCESS;
    }
}
