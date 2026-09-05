import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AvailabilityController from '@/actions/App/Http/Controllers/Settings/AvailabilityController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/availability';

const WEEKDAY_LABELS = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
];

type AvailabilityRuleData = {
    id: number;
    weekday: number;
    starts_at: string;
    ends_at: string;
    slot_length_minutes: number;
    is_active: boolean;
};

type SlotExceptionData = {
    id: number;
    date: string;
    is_closed: boolean;
    override_starts_at: string | null;
    override_ends_at: string | null;
    reason: string | null;
};

export default function AvailabilitySettings({
    rules,
    exceptions,
}: {
    rules: AvailabilityRuleData[];
    exceptions: SlotExceptionData[];
}) {
    const ruleByWeekday = new Map(rules.map((r) => [r.weekday, r]));

    return (
        <>
            <Head title="Availability" />

            <h1 className="sr-only">Availability</h1>

            <div className="space-y-10">
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Working hours"
                        description="When clients can book you, per day of the week"
                    />

                    <div className="space-y-4">
                        {WEEKDAY_LABELS.map((label, weekday) => (
                            <WeekdayRow
                                key={weekday}
                                weekday={weekday}
                                label={label}
                                rule={ruleByWeekday.get(weekday) ?? null}
                            />
                        ))}
                    </div>
                </div>

                <div className="space-y-6 border-t pt-6">
                    <Heading
                        variant="small"
                        title="Exceptions"
                        description="One-off closures or extra hours for a specific date"
                    />

                    <ExceptionsList exceptions={exceptions} />
                    <NewExceptionForm />
                </div>
            </div>
        </>
    );
}

AvailabilitySettings.layout = {
    breadcrumbs: [
        {
            title: 'Availability',
            href: edit(),
        },
    ],
};

function WeekdayRow({
    weekday,
    label,
    rule,
}: {
    weekday: number;
    label: string;
    rule: AvailabilityRuleData | null;
}) {
    const [closed, setClosed] = useState(rule === null);

    return (
        <div className="grid grid-cols-1 gap-3 rounded-lg border p-4 sm:grid-cols-[100px_1fr_1fr_120px_auto] sm:items-end">
            <Form
                {...AvailabilityController.upsertRule.form()}
                options={{ preserveScroll: true }}
                className="contents"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="flex items-center gap-2 sm:pb-2">
                            <Checkbox
                                id={`active-${weekday}`}
                                checked={!closed}
                                onCheckedChange={(checked) =>
                                    setClosed(!checked)
                                }
                            />
                            <Label htmlFor={`active-${weekday}`}>
                                {label}
                            </Label>
                        </div>

                        <input type="hidden" name="weekday" value={weekday} />
                        <input
                            type="hidden"
                            name="is_active"
                            value={closed ? '0' : '1'}
                        />

                        <div className="grid gap-1">
                            <Label
                                htmlFor={`starts-${weekday}`}
                                className="text-xs"
                            >
                                Start
                            </Label>
                            <Input
                                id={`starts-${weekday}`}
                                type="time"
                                name="starts_at"
                                defaultValue={rule?.starts_at?.slice(0, 5) ?? '09:00'}
                                disabled={closed}
                                required={!closed}
                            />
                            <InputError message={errors.starts_at} />
                        </div>

                        <div className="grid gap-1">
                            <Label
                                htmlFor={`ends-${weekday}`}
                                className="text-xs"
                            >
                                End
                            </Label>
                            <Input
                                id={`ends-${weekday}`}
                                type="time"
                                name="ends_at"
                                defaultValue={rule?.ends_at?.slice(0, 5) ?? '18:00'}
                                disabled={closed}
                                required={!closed}
                            />
                            <InputError message={errors.ends_at} />
                        </div>

                        <div className="grid gap-1">
                            <Label
                                htmlFor={`slot-${weekday}`}
                                className="text-xs"
                            >
                                Slot (min)
                            </Label>
                            <Input
                                id={`slot-${weekday}`}
                                type="number"
                                min={5}
                                max={480}
                                name="slot_length_minutes"
                                defaultValue={rule?.slot_length_minutes ?? 30}
                                disabled={closed}
                                required={!closed}
                            />
                            <InputError
                                message={errors.slot_length_minutes}
                            />
                        </div>

                        <Button
                            size="sm"
                            disabled={processing}
                            data-test={`save-weekday-${weekday}`}
                        >
                            Save
                        </Button>
                    </>
                )}
            </Form>

            {rule && (
                <button
                    type="button"
                    className="text-muted-foreground hover:text-destructive col-span-full justify-self-start text-xs underline sm:col-start-5 sm:justify-self-end"
                    onClick={() =>
                        router.delete(
                            AvailabilityController.destroyRule.url(rule.id),
                            { preserveScroll: true },
                        )
                    }
                >
                    Remove hours for {label}
                </button>
            )}
        </div>
    );
}

function ExceptionsList({
    exceptions,
}: {
    exceptions: SlotExceptionData[];
}) {
    if (exceptions.length === 0) {
        return (
            <p className="text-muted-foreground text-sm">
                No upcoming exceptions.
            </p>
        );
    }

    return (
        <ul className="divide-y rounded-lg border">
            {exceptions.map((exception) => (
                <li
                    key={exception.id}
                    className="flex items-center justify-between gap-4 p-3"
                >
                    <div>
                        <p className="font-medium">{exception.date}</p>
                        <p className="text-muted-foreground text-sm">
                            {exception.is_closed
                                ? 'Closed'
                                : `Extra hours: ${exception.override_starts_at?.slice(0, 5)}–${exception.override_ends_at?.slice(0, 5)}`}
                            {exception.reason ? ` — ${exception.reason}` : ''}
                        </p>
                    </div>
                    <button
                        type="button"
                        className="text-muted-foreground hover:text-destructive text-xs underline"
                        onClick={() =>
                            router.delete(
                                AvailabilityController.destroyException.url(
                                    exception.id,
                                ),
                                { preserveScroll: true },
                            )
                        }
                    >
                        Remove
                    </button>
                </li>
            ))}
        </ul>
    );
}

function NewExceptionForm() {
    const [isClosed, setIsClosed] = useState(true);

    return (
        <Form
            {...AvailabilityController.storeException.form()}
            options={{ preserveScroll: true, resetOnSuccess: true }}
            className="grid grid-cols-1 gap-3 rounded-lg border p-4 sm:grid-cols-[1fr_auto_1fr_1fr_1fr_auto]"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-1">
                        <Label htmlFor="exception-date" className="text-xs">
                            Date
                        </Label>
                        <Input
                            id="exception-date"
                            type="date"
                            name="date"
                            required
                        />
                        <InputError message={errors.date} />
                    </div>

                    <div className="flex items-center gap-2 sm:pb-2">
                        <Checkbox
                            id="exception-closed"
                            checked={isClosed}
                            onCheckedChange={(checked) =>
                                setIsClosed(checked === true)
                            }
                        />
                        <input
                            type="hidden"
                            name="is_closed"
                            value={isClosed ? '1' : '0'}
                        />
                        <Label htmlFor="exception-closed">Closed</Label>
                    </div>

                    <div className="grid gap-1">
                        <Label
                            htmlFor="exception-starts"
                            className="text-xs"
                        >
                            Start (if open)
                        </Label>
                        <Input
                            id="exception-starts"
                            type="time"
                            name="override_starts_at"
                            disabled={isClosed}
                        />
                        <InputError message={errors.override_starts_at} />
                    </div>

                    <div className="grid gap-1">
                        <Label htmlFor="exception-ends" className="text-xs">
                            End (if open)
                        </Label>
                        <Input
                            id="exception-ends"
                            type="time"
                            name="override_ends_at"
                            disabled={isClosed}
                        />
                        <InputError message={errors.override_ends_at} />
                    </div>

                    <div className="grid gap-1">
                        <Label htmlFor="exception-reason" className="text-xs">
                            Reason
                        </Label>
                        <Input
                            id="exception-reason"
                            type="text"
                            name="reason"
                            placeholder="Optional"
                        />
                        <InputError message={errors.reason} />
                    </div>

                    <Button
                        size="sm"
                        disabled={processing}
                        data-test="add-exception-button"
                    >
                        Add
                    </Button>
                </>
            )}
        </Form>
    );
}
