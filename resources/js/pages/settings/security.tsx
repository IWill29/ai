import { Head } from '@inertiajs/react';
import SecurityPasswordPanel from '@/components/settings/security-password-panel';
import { edit } from '@/routes/security';

type Props = Readonly<{
    passwordRules: string;
}>;

export default function Security({ passwordRules }: Props) {
    return (
        <>
            <Head title="Security" />
            <SecurityPasswordPanel passwordRules={passwordRules} />
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Security',
            href: edit(),
        },
    ],
};
