import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth, name } = usePage().props;

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-background p-6 text-foreground lg:justify-center lg:p-8">
                <header className="mb-6 w-full max-w-4xl text-sm">
                    <nav className="flex items-center justify-end gap-2">
                        {auth.user ? (
                            <Button asChild variant="outline" size="sm">
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button asChild variant="ghost" size="sm">
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                {canRegister && (
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={register()}>Register</Link>
                                    </Button>
                                )}
                            </>
                        )}
                    </nav>
                </header>
                <div className="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
                    <main className="flex w-full max-w-4xl flex-col items-center gap-8 text-center">
                        <div className="flex size-16 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                            <AppLogoIcon className="size-10 fill-current" />
                        </div>
                        <div className="space-y-3">
                            <h1 className="font-display text-3xl font-semibold tracking-tight">
                                {name}
                            </h1>
                            <p className="mx-auto max-w-md text-balance text-muted-foreground">
                                A modular admin panel built on the Laravel
                                starter kit — Inertia, React, and a fully
                                themable shell.
                            </p>
                        </div>
                        <div className="flex items-center gap-3">
                            <Button asChild>
                                <a
                                    href="https://laravel.com/docs"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Read the docs
                                </a>
                            </Button>
                            <Button asChild variant="secondary">
                                <a
                                    href="https://laracasts.com"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Watch tutorials
                                </a>
                            </Button>
                        </div>
                    </main>
                </div>
                <div className="hidden h-14.5 lg:block"></div>
            </div>
        </>
    );
}
