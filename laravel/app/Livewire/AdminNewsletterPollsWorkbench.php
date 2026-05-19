<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class AdminNewsletterPollsWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $type = '';

    public string $section = 'subscribers';

    public string $role = 'catalog';

    public function setSection(string $section): void
    {
        if (! in_array($section, $this->allowedSections(), true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearFilters(): void
    {
        $this->storeView = '';
        $this->storeId = '';
        $this->query = '';
        $this->status = '';
        $this->type = '';
        $this->section = 'subscribers';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->type = $this->allowedType();
        $this->section = $this->allowedSection();
        $this->role = $this->allowedRole();

        $canViewNewsletterPolls = $this->canViewNewsletterPolls($policy);
        $filters = $this->snapshotFilters();
        $newsletterSnapshot = $canViewNewsletterPolls ? $queryService->snapshot('newsletter', $filters) : null;
        $pollSnapshot = $canViewNewsletterPolls ? $queryService->snapshot('poll', $filters) : null;

        $subscriberRows = $this->decorateNewsletterRows($this->snapshotRows($newsletterSnapshot));
        $templateRows = $this->templateRows($subscriberRows);
        $queueRows = $this->queueRows($subscriberRows);
        $pollRows = $this->decoratePollRows($this->snapshotRows($pollSnapshot));
        $answerRows = $this->answerRows($this->snapshotRows($pollSnapshot));
        $problemRows = array_values(array_filter([...$subscriberRows, ...$pollRows, ...$answerRows], fn (array $row): bool => (bool) $row['is_problem']));

        return view('livewire.admin-newsletter-polls-workbench', [
            'newsletterFeature' => $catalog->get('newsletter'),
            'pollFeature' => $catalog->get('poll'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('newsletter')->featureIds,
                ...$catalog->get('poll')->featureIds,
            ])),
            'canViewNewsletterPolls' => $canViewNewsletterPolls,
            'subscriberRows' => $subscriberRows,
            'templateRows' => $templateRows,
            'queueRows' => $queueRows,
            'pollRows' => $pollRows,
            'answerRows' => $answerRows,
            'problemRows' => $problemRows,
            'currentRows' => $this->filterRows($this->currentRows($subscriberRows, $templateRows, $queueRows, $pollRows, $answerRows, $problemRows)),
            'activeFilters' => $this->activeFilters(),
            'snapshotStoreView' => $newsletterSnapshot['store_view'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFilters(): array
    {
        return array_filter([
            'store_id' => $this->allowedStoreId(),
            'store_view' => $this->allowedStoreView(),
        ], fn (mixed $value): bool => $value !== '' && $value !== null);
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return list<array<string, mixed>>
     */
    private function snapshotRows(?array $snapshot): array
    {
        return $snapshot['payload']['rows'] ?? [];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decorateNewsletterRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $email = $this->nestedArray($payload, 'email');
                $optIn = $this->nestedArray($payload, 'opt_in');
                $problem = $this->nestedArray($payload, 'problem_report');
                $permissions = $this->nestedArray($payload, 'permissions');
                $status = (string) ($payload['state'] ?? $payload['subscriber_status'] ?? 'subscribed');
                $queueStatus = (string) ($email['delivery_status'] ?? 'not_queued');
                $isProblem = $status === 'problem_report' || $queueStatus === 'failed' || $problem !== [] || ($permissions['can_view_recipient'] ?? true) === false;

                return $this->baseRow($row, [
                    'domain' => 'newsletter',
                    'type' => 'subscriber',
                    'type_label' => 'Subscriber',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'queue_status' => $queueStatus,
                    'queue_label' => Str::headline($queueStatus),
                    'title' => $this->firstString($payload['customer_name'] ?? null, $payload['customer_email'] ?? null, 'Newsletter subscriber'),
                    'subtitle' => $this->firstString($payload['customer_email'] ?? null),
                    'summary' => $this->valueSummary(array_filter([
                        'list' => $payload['list'] ?? null,
                        'source' => $payload['source'] ?? null,
                        'opt_in' => $optIn['mode'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'detail' => $this->valueSummary(array_filter([
                        'template' => $email['template'] ?? null,
                        'subject' => $email['subject'] ?? null,
                        'problem' => $problem['type'] ?? null,
                        'denied_reason' => $permissions['denied_reason'] ?? null,
                        'failure' => $email['failure_reason'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== '')),
                    'metric' => $this->firstString($email['message_id'] ?? null, $queueStatus),
                    'is_problem' => $isProblem,
                    'action_label' => 'Preview Subscriber',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function templateRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row): array => [
                ...$row,
                'type' => 'template',
                'type_label' => 'Newsletter template',
                'status' => $row['is_problem'] ? 'problem' : 'valid',
                'status_label' => $row['is_problem'] ? 'Problem' : 'Valid',
                'title' => $this->firstString(Str::before(Str::after($row['detail'], 'Template: '), ' / '), $row['title']),
                'subtitle' => $row['title'],
                'summary' => 'Template diagnostic derived from newsletter queue/subscriber fixture.',
                'metric' => 'template_actual yes',
                'action_label' => 'Preview Template',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function queueRows(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row): array => [
                ...$row,
                'type' => 'queue',
                'type_label' => 'Newsletter queue',
                'status' => $row['queue_status'],
                'status_label' => $row['queue_label'],
                'summary' => 'Queued newsletter email diagnostic for '.$row['subtitle'],
                'action_label' => 'Inspect Queue',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function decoratePollRows(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $status = (string) ($payload['status'] ?? 'active');
                $problem = $this->nestedArray($payload, 'problem');
                $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
                $schedule = $this->nestedArray($payload, 'schedule');
                $voteGuard = $this->nestedArray($payload, 'vote_guard');
                $isProblem = (bool) ($payload['is_problem'] ?? false) || $status === 'invalid' || $problem !== [];

                return $this->baseRow($row, [
                    'domain' => 'poll',
                    'type' => 'poll',
                    'type_label' => 'Poll',
                    'status' => $status,
                    'status_label' => Str::headline($status),
                    'queue_status' => 'not_queued',
                    'queue_label' => 'Not queued',
                    'title' => $this->firstString($payload['question'] ?? null, $payload['code'] ?? null, 'Poll'),
                    'subtitle' => $this->firstString($payload['code'] ?? null),
                    'summary' => $this->answerSummary($answers),
                    'detail' => $this->valueSummary(array_filter([
                        'visibility' => $payload['visibility'] ?? null,
                        'vote_guard' => $voteGuard,
                        'starts_at' => $schedule['starts_at'] ?? null,
                        'ends_at' => $schedule['ends_at'] ?? null,
                        'problem' => $problem['message'] ?? null,
                    ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== '')),
                    'metric' => (string) ($payload['total_votes'] ?? 0).' votes',
                    'is_problem' => $isProblem,
                    'action_label' => 'Preview Poll',
                ]);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     */
    private function answerSummary(array $answers): string
    {
        return collect($answers)
            ->map(fn (array $answer): string => $this->firstString($answer['label'] ?? null, '[missing label]').' ('.(int) ($answer['votes'] ?? 0).')')
            ->implode(' / ');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function answerRows(array $rows): array
    {
        return collect($rows)
            ->flatMap(function (array $row): array {
                $payload = $row['payload'] ?? [];
                $answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
                $problem = $this->nestedArray($payload, 'problem');
                $pollStatus = (string) ($payload['status'] ?? 'active');

                return collect($answers)
                    ->map(function (array $answer, int $index) use ($payload, $pollStatus, $problem, $row): array {
                        $label = $this->firstString($answer['label'] ?? null, '[missing label]');
                        $answerProblem = $this->nestedArray($answer, 'problem');
                        $isProblem = $label === '[missing label]' || $answerProblem !== [];

                        return $this->baseRow($row, [
                            'domain' => 'poll',
                            'type' => 'poll_answer',
                            'type_label' => 'Poll answer',
                            'status' => $pollStatus,
                            'status_label' => Str::headline($pollStatus),
                            'queue_status' => 'not_queued',
                            'queue_label' => 'Not queued',
                            'title' => $label,
                            'subtitle' => $this->firstString($payload['question'] ?? null, $payload['code'] ?? null),
                            'summary' => $this->firstString($payload['code'] ?? null),
                            'detail' => $this->valueSummary(array_filter([
                                'answer_id' => $answer['answer_id'] ?? null,
                                'poll_id' => $payload['poll_id'] ?? null,
                                'problem' => $problem['message'] ?? null,
                            ], fn (mixed $value): bool => $value !== null && $value !== '')),
                            'metric' => (string) ($answer['votes'] ?? 0).' votes',
                            'is_problem' => $isProblem,
                            'action_label' => 'Preview Answer',
                            'entity_id' => ((int) ($row['entity_id'] ?? 0) * 100) + $index,
                        ]);
                    })
                    ->all();
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function baseRow(array $row, array $values): array
    {
        return [
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            ...$values,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterRows(array $rows): array
    {
        $query = Str::lower(trim(Str::substr($this->query, 0, 128)));
        $status = $this->allowedStatus();
        $type = $this->allowedType();

        return collect($rows)
            ->filter(function (array $row) use ($query, $status, $type): bool {
                if ($status === 'problem' && ! $row['is_problem']) {
                    return false;
                }

                if ($status !== '' && $status !== 'problem' && $row['status'] !== $status && $row['queue_status'] !== $status) {
                    return false;
                }

                if ($type !== '' && $row['type'] !== $type && $row['domain'] !== $type) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                return Str::contains($this->rowHaystack($row), $query);
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowHaystack(array $row): string
    {
        return Str::lower(implode(' ', array_filter([
            $row['domain'] ?? '',
            $row['type_label'] ?? '',
            $row['status_label'] ?? '',
            $row['queue_label'] ?? '',
            $row['title'] ?? '',
            $row['subtitle'] ?? '',
            $row['summary'] ?? '',
            $row['detail'] ?? '',
            $row['metric'] ?? '',
            $row['store_view'] ?? '',
        ])));
    }

    /**
     * @param  list<array<string, mixed>>  $subscriberRows
     * @param  list<array<string, mixed>>  $templateRows
     * @param  list<array<string, mixed>>  $queueRows
     * @param  list<array<string, mixed>>  $pollRows
     * @param  list<array<string, mixed>>  $answerRows
     * @param  list<array<string, mixed>>  $problemRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $subscriberRows, array $templateRows, array $queueRows, array $pollRows, array $answerRows, array $problemRows): array
    {
        return match ($this->allowedSection()) {
            'templates' => $templateRows,
            'queue' => $queueRows,
            'polls' => $pollRows,
            'answers' => $answerRows,
            'problems' => $problemRows,
            default => $subscriberRows,
        };
    }

    /**
     * @return array<string, string>
     */
    private function activeFilters(): array
    {
        return array_filter([
            'store' => $this->allowedStoreView(),
            'store_id' => $this->allowedStoreId(),
            'query' => trim(Str::substr($this->query, 0, 128)),
            'status' => $this->allowedStatus(),
            'type' => $this->allowedType(),
        ], fn (string $value): bool => $value !== '');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function nestedArray(array $payload, string $key): array
    {
        return is_array($payload[$key] ?? null) ? $payload[$key] : [];
    }

    private function firstString(mixed ...$values): string
    {
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                $value = $this->valueSummary($value);
            }

            $stringValue = trim((string) $value);

            if ($stringValue !== '') {
                return $stringValue;
            }
        }

        return '';
    }

    private function valueSummary(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $nestedValue, string|int $nestedKey): string => is_int($nestedKey)
                    ? $this->valueSummary($nestedValue)
                    : Str::headline((string) $nestedKey).': '.$this->valueSummary($nestedValue))
                ->filter()
                ->implode(' / ');
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return (string) $value;
    }

    /**
     * @return list<string>
     */
    private function allowedSections(): array
    {
        return ['subscribers', 'templates', 'queue', 'polls', 'answers', 'problems'];
    }

    private function allowedSection(): string
    {
        return in_array($this->section, $this->allowedSections(), true) ? $this->section : 'subscribers';
    }

    private function allowedStoreId(): string
    {
        return in_array($this->storeId, ['', '9001', '9002'], true) ? $this->storeId : '';
    }

    private function allowedStoreView(): string
    {
        return in_array($this->storeView, ['', 'default', 'de'], true) ? $this->storeView : '';
    }

    private function allowedStatus(): string
    {
        return in_array($this->status, ['', 'subscribed', 'unsubscribed', 'sent', 'queued', 'failed', 'problem_report', 'active', 'closed', 'invalid', 'valid', 'problem'], true)
            ? $this->status
            : '';
    }

    private function allowedType(): string
    {
        return in_array($this->type, ['', 'subscriber', 'template', 'queue', 'poll', 'poll_answer'], true) ? $this->type : '';
    }

    private function allowedRole(): string
    {
        return in_array($this->role, ['catalog', 'read-only', 'full', 'denied'], true) ? $this->role : 'catalog';
    }

    private function canViewNewsletterPolls(DomainPolicy $policy): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        return $policy->viewDiagnostics($this->fixtureUser());
    }

    private function fixtureUser(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 918,
            'name' => "{$this->role} admin newsletter polls fixture",
            'email' => "{$this->role}-admin-newsletter-polls@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
