<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\CommunicationService;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class CommunicationsWorkbench extends Component
{
    public string $storeView = '';

    public string $storeId = '';

    public string $query = '';

    public string $status = '';

    public string $channel = '';

    public string $section = 'newsletter';

    public string $role = 'customer';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['newsletter', 'contact', 'alerts', 'queue'], true)) {
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
        $this->channel = '';
        $this->section = 'newsletter';
    }

    public function render(
        CommunicationService $communicationService,
        DomainCatalog $catalog,
        DomainQueryService $queryService,
        DomainPolicy $policy,
    ): View {
        $this->storeId = $this->allowedStoreId();
        $this->storeView = $this->allowedStoreView();
        $this->status = $this->allowedStatus();
        $this->channel = $this->allowedChannel();
        $this->section = $this->allowedSection();

        $canViewCommunications = $this->canViewCommunications($policy);
        $filters = $this->snapshotFilters();
        $newsletterSnapshot = $canViewCommunications ? $queryService->snapshot('newsletter', $filters) : null;
        $contactSnapshot = $canViewCommunications ? $queryService->snapshot('contact', $filters) : null;

        $newsletterRows = $this->filterRows($this->decorateRows($this->snapshotRows($newsletterSnapshot), 'newsletter'));
        $contactRows = $this->filterRows($this->decorateRows($this->snapshotRows($contactSnapshot), 'contact'));
        $alertRows = $this->filterRows($this->alertRows($this->decorateRows($this->snapshotRows($contactSnapshot), 'contact')));
        $queueRows = $this->filterRows([
            ...$this->queueRows($this->decorateRows($this->snapshotRows($newsletterSnapshot), 'newsletter')),
            ...$this->queueRows($this->decorateRows($this->snapshotRows($contactSnapshot), 'contact')),
        ]);

        $selectedKind = (string) (($this->currentRows($newsletterRows, $contactRows, $alertRows, $queueRows)[0]['kind'] ?? 'newsletter'));
        $selectedRecipient = (string) (($this->currentRows($newsletterRows, $contactRows, $alertRows, $queueRows)[0]['recipient'] ?? 'customer@example.test'));

        return view('livewire.communications-workbench', [
            'newsletterFeature' => $catalog->get('newsletter'),
            'contactFeature' => $catalog->get('contact'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('newsletter')->featureIds,
                ...$catalog->get('contact')->featureIds,
            ])),
            'canViewCommunications' => $canViewCommunications,
            'newsletterRows' => $newsletterRows,
            'contactRows' => $contactRows,
            'alertRows' => $alertRows,
            'queueRows' => $queueRows,
            'currentRows' => $this->currentRows($newsletterRows, $contactRows, $alertRows, $queueRows),
            'activeFilters' => $this->activeFilters(),
            'mailPlan' => $communicationService->plan($selectedKind, $selectedRecipient),
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
    private function decorateRows(array $rows, string $domain): array
    {
        return collect($rows)
            ->map(fn (array $row): array => $this->decorateRow($row, $domain))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function decorateRow(array $row, string $domain): array
    {
        $payload = $row['payload'] ?? [];
        $kind = $this->kind($payload, $domain);
        $queue = is_array($payload['queue'] ?? null) ? $payload['queue'] : [];
        $email = $this->nestedArray($payload, 'email');
        $problemReport = $this->nestedArray($payload, 'problem_report');
        $queueStatus = $this->queueStatus($payload, $queue, $email);
        $status = $this->status($payload);
        $lastError = $this->lastError($payload, $queue, $email, $problemReport);

        return [
            'domain' => $domain,
            'kind' => $kind,
            'kind_label' => Str::headline($kind),
            'entity_id' => (int) ($row['entity_id'] ?? 0),
            'store_id' => (int) ($row['store_id'] ?? 0),
            'store_view' => (string) ($row['store_view'] ?? ''),
            'title' => $this->title($payload, $kind),
            'recipient' => $this->recipient($payload, $email),
            'sender' => $this->sender($payload),
            'status' => $status,
            'status_label' => Str::headline($status),
            'message' => $this->message($payload, $email, $problemReport),
            'product_sku' => (string) ($payload['product_sku'] ?? $payload['sku'] ?? ''),
            'product_name' => (string) ($payload['product_name'] ?? ''),
            'queue_status' => $queueStatus,
            'queue_label' => Str::headline($queueStatus),
            'attempts' => (int) ($payload['attempts'] ?? $queue['attempts'] ?? $problemReport['attempts'] ?? 0),
            'last_error' => $lastError,
            'scheduled_at' => $this->scheduledAt($payload, $queue, $email),
            'is_problem' => $this->isProblem($payload, $email, $problemReport, $queueStatus, $status),
            'requires_confirmation' => (bool) ($payload['requires_confirmation'] ?? false),
            'summary' => $this->summary($payload, $kind),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function kind(array $payload, string $domain): string
    {
        return (string) ($payload['kind'] ?? $payload['communication_type'] ?? $payload['type'] ?? $payload['channel'] ?? ($domain === 'newsletter' ? 'newsletter' : 'contact_form'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function title(array $payload, string $kind): string
    {
        $email = $this->nestedArray($payload, 'email');
        $form = $this->nestedArray($payload, 'form');
        $ui = $this->nestedArray($payload, 'ui');

        return $this->firstString(
            $payload['title'] ?? null,
            $form['subject'] ?? null,
            $email['subject'] ?? null,
            $payload['subject'] ?? null,
            $ui['badge'] ?? null,
            $email['template'] ?? null,
            $payload['template'] ?? null,
            Str::headline($kind),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function status(array $payload): string
    {
        return (string) ($payload['status'] ?? $payload['subscriber_status'] ?? $payload['state'] ?? 'active');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function summary(array $payload, string $kind): string
    {
        $form = $this->nestedArray($payload, 'form');
        $ui = $this->nestedArray($payload, 'ui');

        if (($ui['summary'] ?? '') !== '') {
            return (string) $ui['summary'];
        }

        return match ($kind) {
            'newsletter' => $this->valueSummary($payload['segments'] ?? $payload['stores'] ?? $payload['groups'] ?? 'Subscriber'),
            'contact_form' => $this->valueSummary($payload['department'] ?? $payload['reason'] ?? $form['subject'] ?? 'Contact request'),
            'send_to_friend' => $this->valueSummary($this->recipientList($payload) ?: ($payload['recipient'] ?? 'Send to friend')),
            'product_alert' => $this->valueSummary(array_filter([
                $payload['alert_type'] ?? null,
                $payload['product_sku'] ?? null,
            ])),
            default => $this->valueSummary($payload['context'] ?? $payload['metadata'] ?? $kind),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $email
     */
    private function recipient(array $payload, array $email): string
    {
        return $this->firstString(
            $email['recipient'] ?? null,
            $payload['recipient'] ?? null,
            $payload['customer_email'] ?? null,
            $payload['sender_email'] ?? null,
            $this->recipientList($payload),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sender(array $payload): string
    {
        $sender = $this->nestedArray($payload, 'sender');
        $form = $this->nestedArray($payload, 'form');

        return $this->firstString(
            $sender['name'] ?? null,
            $sender['email'] ?? null,
            $payload['sender_name'] ?? null,
            $payload['customer_name'] ?? null,
            $form['name'] ?? null,
            $payload['name'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $email
     * @param  array<string, mixed>  $problemReport
     */
    private function message(array $payload, array $email, array $problemReport): string
    {
        return $this->firstString(
            $this->nestedArray($payload, 'form')['message_preview'] ?? null,
            $payload['message_preview'] ?? null,
            $payload['message'] ?? null,
            $payload['comment'] ?? null,
            $problemReport === [] ? null : $this->valueSummary($problemReport),
            $email['failure_reason'] ?? null,
            $payload['note'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $queue
     * @param  array<string, mixed>  $email
     */
    private function queueStatus(array $payload, array $queue, array $email): string
    {
        return $this->firstString(
            $payload['queue_status'] ?? null,
            $queue['status'] ?? null,
            $email['delivery_status'] ?? null,
            'not_queued',
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $queue
     * @param  array<string, mixed>  $email
     * @param  array<string, mixed>  $problemReport
     */
    private function lastError(array $payload, array $queue, array $email, array $problemReport): string
    {
        return $this->firstString(
            $payload['last_error'] ?? null,
            $queue['last_error'] ?? null,
            $email['failure_reason'] ?? null,
            $problemReport['last_smtp_code'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $queue
     * @param  array<string, mixed>  $email
     */
    private function scheduledAt(array $payload, array $queue, array $email): string
    {
        return $this->firstString(
            $payload['scheduled_at'] ?? null,
            $queue['scheduled_at'] ?? null,
            $email['queued_at'] ?? null,
            $email['sent_at'] ?? null,
            $email['failed_at'] ?? null,
            $payload['created_at'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $email
     * @param  array<string, mixed>  $problemReport
     */
    private function isProblem(array $payload, array $email, array $problemReport, string $queueStatus, string $status): bool
    {
        return (bool) ($payload['is_problem'] ?? $payload['has_problem'] ?? false)
            || $problemReport !== []
            || $queueStatus === 'failed'
            || ($email['delivery_status'] ?? '') === 'failed'
            || in_array($status, ['failed', 'problem', 'problem_report'], true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function nestedArray(array $payload, string $key): array
    {
        return is_array($payload[$key] ?? null) ? $payload[$key] : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recipientList(array $payload): string
    {
        if (! is_array($payload['recipients'] ?? null)) {
            return '';
        }

        return collect($payload['recipients'])
            ->map(fn (mixed $recipient): string => is_array($recipient)
                ? $this->firstString($recipient['email'] ?? null, $recipient['name'] ?? null)
                : $this->firstString($recipient))
            ->filter()
            ->implode(', ');
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
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function alertRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => in_array($row['kind'], ['send_to_friend', 'product_alert'], true))
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
            ->filter(fn (array $row): bool => $row['queue_status'] !== 'not_queued' || $row['attempts'] > 0)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function filterRows(array $rows): array
    {
        $query = Str::lower(trim(Str::substr($this->query, 0, 128)));
        $status = $this->allowedStatus();
        $channel = $this->allowedChannel();

        return collect($rows)
            ->filter(function (array $row) use ($channel, $query, $status): bool {
                if ($status !== '' && $row['status'] !== $status && $row['queue_status'] !== $status) {
                    return false;
                }

                if ($channel !== '' && $row['kind'] !== $channel) {
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
            $row['kind_label'] ?? '',
            $row['title'] ?? '',
            $row['recipient'] ?? '',
            $row['sender'] ?? '',
            $row['status_label'] ?? '',
            $row['queue_label'] ?? '',
            $row['message'] ?? '',
            $row['product_sku'] ?? '',
            $row['product_name'] ?? '',
            $row['summary'] ?? '',
            $row['last_error'] ?? '',
        ])));
    }

    /**
     * @param  list<array<string, mixed>>  $newsletterRows
     * @param  list<array<string, mixed>>  $contactRows
     * @param  list<array<string, mixed>>  $alertRows
     * @param  list<array<string, mixed>>  $queueRows
     * @return list<array<string, mixed>>
     */
    private function currentRows(array $newsletterRows, array $contactRows, array $alertRows, array $queueRows): array
    {
        return match ($this->allowedSection()) {
            'contact' => $contactRows,
            'alerts' => $alertRows,
            'queue' => $queueRows,
            default => $newsletterRows,
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
            'channel' => $this->allowedChannel(),
        ], fn (string $value): bool => $value !== '');
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
        return in_array($this->status, [
            '',
            'subscribed',
            'unsubscribed',
            'pending',
            'not_active',
            'unconfirmed',
            'queued',
            'sending',
            'sent',
            'failed',
            'paused',
            'cancelled',
            'not_sent',
            'not_queued',
            'blocked',
            'denied',
            'valid',
            'invalid',
            'problem',
            'problem_report',
            'permission_denied',
        ], true) ? $this->status : '';
    }

    private function allowedChannel(): string
    {
        return in_array($this->channel, ['', 'newsletter', 'contact_form', 'send_to_friend', 'product_alert'], true) ? $this->channel : '';
    }

    private function allowedSection(): string
    {
        return in_array($this->section, ['newsletter', 'contact', 'alerts', 'queue'], true) ? $this->section : 'newsletter';
    }

    private function canViewCommunications(DomainPolicy $policy): bool
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
            'id' => 916,
            'name' => "{$this->role} communications fixture",
            'email' => "{$this->role}-communications@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
