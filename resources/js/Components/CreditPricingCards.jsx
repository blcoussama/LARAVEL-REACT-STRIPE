import { usePage } from "@inertiajs/react";

export default function CreditPricingCards({ packages, features }) {
    const { csrf_token } = usePage().props;

    return (
        <section className="bg-gray-900">
            <div className="py-8 px-4">
                <div className="text-center mb-8">
                    <h2 className="mb-4 text-4xl font-extrabold text-white">
                        The more credits you choose the bigger savings you will make.
                    </h2>
                </div>

                <div className="space-y-8 lg:grid lg:grid-cols-3 sm:gap-6 xl:gap-10 lg:space-y-0">
                    {packages.map((p) => (
                        <div key={p.id} className="flex flex-col align-stretch p-6 mx-auto lg:mx-0 max-w-lg text-center border shadow-sm border-gray-600 bg-gray-900 rounded-lg text-white">
                            <h3 className="mb-4 text-2xl font-semibold">{p.name}</h3>
                            <div className="flex justify-center items-baseline my-8">
                                <span className="mb-4 text-3xl font-bold">
                                    {p.price} <span className="text-lg font-normal">USD</span>
                                </span>
                                <span className="mb-4 text-gray-400">
                                    /{p.credits} credits
                                </span>
                            </div>

                            <ul role="list" className="mb-8 space-y-4 text-left">
                                {features.map((f) => (
                                    <li key={f.id} className="flex items-center space-x-3">
                                        <svg className="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M16.707 5.293a1 1 0 00-1.414-1.414l-7.071 7.07-3.536-3.536a1 1 0 00-1.414 1.414l4.243 4.243a1 1 0 001.414 0l8-8z"></path>
                                        </svg>
                                        <span>{f.name}</span>
                                    </li>
                                ))}
                            </ul>

                            <form action={route("credits.buy", p)} method="post" className="w-full">
                                <input type="hidden" name="_token" value={csrf_token} autoComplete="off" />
                                <button type="submit" className="w-full px-5 py-3 text-sm font-medium text-center text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 focus:outline-hidden">
                                    Buy Now
                                </button>
                            </form>
                            
                        </div>
                    ))}
                </div>

            </div>
        </section>
    )
}