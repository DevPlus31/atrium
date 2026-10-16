import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { ConsentForm } from '@/components/privacy/consent-form';
import DocumentLayout from '@/layouts/document-layout';

type LegalDocumentProps = {
    document: App.Modules.Data.LegalDocumentData;
};

/**
 * A legal page. The HTML comes from the document's Markdown file, rendered
 * on the server with raw HTML stripped. The Cookie Policy ends with the
 * cookie choices themselves (the "Your privacy choices" link lands there).
 */
export default function Show({ document }: LegalDocumentProps) {
    const { t } = useLaravelReactI18n();

    return (
        <DocumentLayout title={document.title}>
            <Head title={document.title} />
            <article
                className="legal-prose"
                dangerouslySetInnerHTML={{ __html: document.html }}
            />
            {document.document === 'cookies' && (
                <section
                    id="choices"
                    aria-label={t('Your privacy choices')}
                    className="mt-6 scroll-mt-6 rounded-xl border bg-card p-6"
                >
                    <ConsentForm />
                </section>
            )}
        </DocumentLayout>
    );
}
