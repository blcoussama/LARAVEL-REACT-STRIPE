import ApplicationLogo from '@/Components/ApplicationLogo';
import { Head, Link } from '@inertiajs/react';

const highlights = [
    {
        title: '10 free credits',
        text: 'Every new account starts with 10 credits to try the features.',
    },
    {
        title: 'Pay per use',
        text: 'Each feature costs a fixed number of credits, debited atomically on every use.',
    },
    {
        title: 'Credit packs with Stripe',
        text: 'Top up with Stripe Checkout; credits are added only after a signed Stripe webhook.',
    },
];

export default function Welcome({ auth, canLogin, canRegister }) {
    const linkClass =
        'rounded-md px-3 py-2 text-gray-200 ring-1 ring-transparent transition hover:text-white focus:outline-hidden focus-visible:ring-white';

    return (
        <>
            <Head title="Welcome" />
            <div className="min-h-screen bg-gray-900 text-gray-300">
                <div className="mx-auto max-w-5xl px-6">
                    <header className="flex items-center justify-between py-8">
                        <div className="flex items-center gap-3">
                            <ApplicationLogo className="h-10 w-auto fill-current text-gray-100" />
                            <span className="text-lg font-semibold text-white">Laravel Credits</span>
                        </div>
                        <nav className="flex gap-2">
                            {auth.user ? (
                                <Link href={route('dashboard')} className={linkClass}>
                                    Dashboard
                                </Link>
                            ) : (
                                <>
                                    {canLogin && (
                                        <Link href={route('login')} className={linkClass}>
                                            Log in
                                        </Link>
                                    )}
                                    {canRegister && (
                                        <Link href={route('register')} className={linkClass}>
                                            Register
                                        </Link>
                                    )}
                                </>
                            )}
                        </nav>
                    </header>

                    <main className="py-16">
                        <div className="flex flex-col items-center text-center">
                            <img src="/img/coin.svg" alt="" className="mb-6 w-20" />
                            <h1 className="text-4xl font-extrabold text-white sm:text-5xl">
                                Pay-per-use tools powered by credits
                            </h1>
                            <p className="mt-6 max-w-2xl text-lg text-gray-400">
                                A small SaaS built with Laravel, Inertia.js and React: use the
                                features with your credits and buy more packs through Stripe
                                Checkout.
                            </p>
                            {!auth.user && canRegister && (
                                <Link
                                    href={route('register')}
                                    className="mt-8 rounded-lg bg-blue-600 px-6 py-3 font-medium text-white hover:bg-blue-700"
                                >
                                    Create a free account
                                </Link>
                            )}
                        </div>

                        <div className="mt-16 grid gap-6 sm:grid-cols-3">
                            {highlights.map((item) => (
                                <div
                                    key={item.title}
                                    className="rounded-lg border border-gray-700 bg-gray-800 p-6"
                                >
                                    <h2 className="text-lg font-semibold text-white">{item.title}</h2>
                                    <p className="mt-2 text-sm text-gray-400">{item.text}</p>
                                </div>
                            ))}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
