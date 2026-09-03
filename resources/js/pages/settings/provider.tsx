import { Form, Head } from '@inertiajs/react';
import ProviderController from '@/actions/App/Http/Controllers/Settings/ProviderController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit } from '@/routes/provider';

// Curated, not exhaustive — enough for Waqti's current Algeria-first
// market plus a couple of neighbouring/common zones. Extend if a
// provider outside this list signs up (see DOMAIN_MODEL.md §2, Provider
// carries its own timezone).
const TIMEZONE_OPTIONS = [
    'Africa/Algiers',
    'Africa/Tunis',
    'Africa/Casablanca',
    'Europe/Paris',
    'Europe/London',
    'UTC',
];

type ProviderData = {
    name: string;
    business_category: string | null;
    timezone: string;
    whatsapp_phone_number_id: string | null;
    whatsapp_business_account_id: string | null;
    has_whatsapp_access_token: boolean;
};

export default function ProviderSettings({
    provider,
}: {
    provider: ProviderData | null;
}) {
    return (
        <>
            <Head title="Business settings" />

            <h1 className="sr-only">Business settings</h1>

            <div className="space-y-10">
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Business profile"
                        description="This is the business your clients see in WhatsApp bookings"
                    />

                    <Form
                        {...ProviderController.updateProfile.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Business name</Label>
                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={provider?.name ?? ''}
                                        name="name"
                                        required
                                        placeholder="e.g. Blida Barbershop"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="business_category">
                                        Category
                                    </Label>
                                    <Input
                                        id="business_category"
                                        className="mt-1 block w-full"
                                        defaultValue={
                                            provider?.business_category ?? ''
                                        }
                                        name="business_category"
                                        placeholder="e.g. barbershop, salon, clinic"
                                    />
                                    <InputError
                                        message={errors.business_category}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="timezone">Timezone</Label>
                                    <Select
                                        name="timezone"
                                        defaultValue={
                                            provider?.timezone ??
                                            'Africa/Algiers'
                                        }
                                    >
                                        <SelectTrigger
                                            id="timezone"
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select a timezone" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {TIMEZONE_OPTIONS.map((tz) => (
                                                <SelectItem
                                                    key={tz}
                                                    value={tz}
                                                >
                                                    {tz}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.timezone} />
                                </div>

                                <Button
                                    disabled={processing}
                                    data-test="update-provider-profile-button"
                                >
                                    Save
                                </Button>
                            </>
                        )}
                    </Form>
                </div>

                <div className="space-y-6 border-t pt-6">
                    <Heading
                        variant="small"
                        title="WhatsApp Cloud API credentials"
                        description="From your Meta Developer app — required before bookings can flow through WhatsApp"
                    />

                    <Form
                        {...ProviderController.updateWhatsappCredentials.form()}
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="whatsapp_phone_number_id">
                                        Phone number ID
                                    </Label>
                                    <Input
                                        id="whatsapp_phone_number_id"
                                        className="mt-1 block w-full"
                                        defaultValue={
                                            provider?.whatsapp_phone_number_id ??
                                            ''
                                        }
                                        name="whatsapp_phone_number_id"
                                        required
                                    />
                                    <InputError
                                        message={
                                            errors.whatsapp_phone_number_id
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="whatsapp_business_account_id">
                                        Business account ID
                                    </Label>
                                    <Input
                                        id="whatsapp_business_account_id"
                                        className="mt-1 block w-full"
                                        defaultValue={
                                            provider?.whatsapp_business_account_id ??
                                            ''
                                        }
                                        name="whatsapp_business_account_id"
                                        required
                                    />
                                    <InputError
                                        message={
                                            errors.whatsapp_business_account_id
                                        }
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="whatsapp_access_token">
                                        Access token
                                    </Label>
                                    <Input
                                        id="whatsapp_access_token"
                                        type="password"
                                        className="mt-1 block w-full"
                                        name="whatsapp_access_token"
                                        required={
                                            !provider?.has_whatsapp_access_token
                                        }
                                        placeholder={
                                            provider?.has_whatsapp_access_token
                                                ? 'Stored — leave blank to keep it'
                                                : ''
                                        }
                                        autoComplete="off"
                                    />
                                    <InputError
                                        message={errors.whatsapp_access_token}
                                    />
                                    <p className="text-muted-foreground text-sm">
                                        {provider?.has_whatsapp_access_token
                                            ? 'A token is already stored (encrypted). Leave this blank to keep it.'
                                            : 'Stored encrypted — never shown in plaintext again.'}
                                    </p>
                                </div>

                                <Button
                                    disabled={processing}
                                    data-test="update-whatsapp-credentials-button"
                                >
                                    Save
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

ProviderSettings.layout = {
    breadcrumbs: [
        {
            title: 'Business settings',
            href: edit(),
        },
    ],
};
