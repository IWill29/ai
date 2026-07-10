import { Head } from '@inertiajs/react';
import ProfileSetupPanel from '@/components/settings/profile-setup-panel';
import { edit } from '@/routes/profile';

type Props = Readonly<{
    mustVerifyEmail: boolean;
    status?: string;
}>;

export default function Profile({ mustVerifyEmail, status }: Props) {
    return (
        <>
            <Head title="Profile" />
            <ProfileSetupPanel mustVerifyEmail={mustVerifyEmail} status={status} />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile',
            href: edit(),
        },
    ],
};
