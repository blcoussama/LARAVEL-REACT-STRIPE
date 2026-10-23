import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';

export default function Dashboard({ usedFeatures }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                        <div className='relative overflow-x-auto'>
                            <table className="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead className='text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400'>
                                    <tr>
                                        <th scope="col" className='px-6 py-3'>
                                            Feature
                                        </th>
                                        <th scope="col" className='px-6 py-3'>
                                            Credits
                                        </th>
                                        <th scope="col" className='px-6 py-3'>
                                            Date
                                        </th>
                                        <th scope="col" className='px-6 py-3'>
                                            Additional Data
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {usedFeatures.data.map((feature) => (
                                        <tr key={feature.id} className='bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'>
                                            <td className='px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white'>
                                                {feature.feature.name}
                                            </td>
                                            <td className='px-6 py-4'>
                                                {feature.credits}
                                            </td>
                                            <td className='px-6 py-4'>
                                                {new Date(feature.created_at).toLocaleDateString('en-US', {
                                                    year: 'numeric',
                                                    month: '2-digit',
                                                    day: '2-digit',
                                                    hour: '2-digit',
                                                    minute: '2-digit'
                                                })}
                                            </td>
                                            <td className='px-6 py-4'>
                                                {feature.data ? JSON.stringify(feature.data) : 'No additional data'}
                                            </td>
                                        </tr>
                                    ))}
                                    {!usedFeatures.data.length && (
                                        <tr className='bg-white border-b dark:bg-gray-800 dark:border-gray-700'>
                                            <td colSpan="4" className='px-6 py-4 text-center text-gray-500'>
                                                No used features found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>

                            </table>
                        </div>
                        <Pagination meta={usedFeatures.meta} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
