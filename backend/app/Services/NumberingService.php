<?php

namespace App\Services;

use App\Models\GrantProgram;
use App\Models\NumberingConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NumberingService
{
    private const ROMAN_MONTHS = [
        1 => 'I',
        2 => 'II',
        3 => 'III',
        4 => 'IV',
        5 => 'V',
        6 => 'VI',
        7 => 'VII',
        8 => 'VIII',
        9 => 'IX',
        10 => 'X',
        11 => 'XI',
        12 => 'XII',
    ];

    public function generateNumber(string $documentType, ?GrantProgram $grantProgram = null): string
    {
        return DB::transaction(function () use ($documentType, $grantProgram) {
            $config = $this->resolveConfiguration($documentType, $grantProgram?->id);

            if ($config !== null) {
                // Lock row for update to prevent concurrent duplicate sequence
                $config = NumberingConfiguration::query()
                    ->where('id', $config->id)
                    ->lockForUpdate()
                    ->first();

                $config->increment('current_sequence');
                $seq = $config->current_sequence;

                return $this->formatNumber($config->format_pattern, [
                    'prefix' => $config->prefix ?: strtoupper($documentType),
                    'seq' => str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                    'sequence' => str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                    'raw_seq' => (string) $seq,
                    'year' => date('Y'),
                    'month' => date('m'),
                    'day' => date('d'),
                    'roman_month' => self::ROMAN_MONTHS[(int) date('n')] ?? date('m'),
                    'program_code' => $grantProgram?->code ?: 'GEN',
                    'code' => $grantProgram?->code ?: 'GEN',
                ]);
            }

            // Fallback default format when no configuration is registered
            $prefix = match ($documentType) {
                'approval' => 'APP',
                'decision' => 'DEC',
                'sk', 'decision_letter' => 'SK',
                default => strtoupper(substr($documentType, 0, 4)),
            };

            return sprintf(
                '%s/%s/%s/%s',
                $prefix,
                date('Y'),
                date('m'),
                Str::upper(Str::random(6))
            );
        });
    }

    public function resolveConfiguration(string $documentType, ?string $grantProgramId = null): ?NumberingConfiguration
    {
        if ($grantProgramId !== null) {
            $programConfig = NumberingConfiguration::query()
                ->where('grant_program_id', $grantProgramId)
                ->where('document_type', $documentType)
                ->where('status', 'active')
                ->first();

            if ($programConfig !== null) {
                return $programConfig;
            }
        }

        return NumberingConfiguration::query()
            ->whereNull('grant_program_id')
            ->where('document_type', $documentType)
            ->where('status', 'active')
            ->first();
    }

    private function formatNumber(string $pattern, array $replacements): string
    {
        $formatted = $pattern;
        foreach ($replacements as $key => $value) {
            $formatted = str_replace('{'.$key.'}', $value, $formatted);
        }

        return $formatted;
    }
}
