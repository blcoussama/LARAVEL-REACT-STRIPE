import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, usePage } from "@inertiajs/react";


export default function Feature({ feature, answer, children }) {
    const { auth } = usePage().props;

    const availableCredits = auth.user.available_credits;

    return (
        <AuthenticatedLayout 
            user={auth.user} 
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {feature.name}
                </h2>
            }
        >
            <Head title="Feature 1" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {answer !== null && (
                        <div className="mb-3 py-3 px-5 rounded-sm bg-emerald-600 text-white text-xl">
                            Result of Clculation: {answer}
                        </div>
                    )}

                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-xs sm:rounded-lg relative">
                        {availableCredits !== null && feature.required_credits > availableCredits && (
                            <div className="absolute top-0 left-0 right-0 bottom-0 z-20 flex flex-col items-center justify-center bg-white/70 gap-3">
                                <svg 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke-width="1.5" 
                                    stroke="currentColor"
                                    class="w-12 h-12">
                                        <path 
                                            stroke-linecap="round" 
                                            stroke-linejoin="round" 
                                            d="M12 9v3.75m0 0V15m0-2.25h.008v.008H12V12zm-6 3a6 6 0 1 1 12 0A6 6 0 0 1 6 12zM3.75 12a8.25 8.25 0 1 1 16.5 0A8.25 8.25 0 0 1 3.75 12z">

                                        </path>
                                </svg>

                                <div>
                                    You don't have enough credits to use this feature. Go{" "}
                                    <Link href={route('credit.index')} className="underline">By more Credits</Link>
                                </div>
                            </div>
                        )}

                        <div className="p-8 text-gray-400 border-b pb-4">
                            <p>{feature.description}</p>
                            <p className="text-sm italic text-right">
                                Requires {feature.required_credits} credits
                            </p>
                        </div>

                        {children}

                    </div>
                </div>
            </div>

        </AuthenticatedLayout>
    )
}