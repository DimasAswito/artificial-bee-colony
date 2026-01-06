@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.1/css/dataTables.tailwindcss.css">
@endpush

@section('content')
  <div class="grid grid-cols-12 gap-4 md:gap-6">
    <div class="col-span-12 space-y-6 xl:col-span-7">
      <x-ecommerce.ecommerce-metrics />
      <x-ecommerce.monthly-sale />
    </div>
    <div class="col-span-12 xl:col-span-5">
        <x-ecommerce.monthly-target />
    </div>

    <div class="col-span-12">
      <x-ecommerce.statistics-chart />
    </div>

    <div class="col-span-12 xl:col-span-5">
      <x-ecommerce.customer-demographic />
    </div>

    <div class="col-span-12 xl:col-span-7">
      <x-ecommerce.recent-orders />
    </div>

    <!-- DataTables Test Card -->
    <div class="col-span-12">
        <div class="rounded-sm border border-stroke bg-white px-5 pt-6 pb-2.5 shadow-default dark:border-gray-800 dark:bg-gray-800 sm:px-7.5 xl:pb-1">
            <h4 class="mb-6 text-xl font-bold text-black dark:text-white">
                DataTables Test
            </h4>
            <div class="overflow-x-auto">
                <table id="exampleTable" class="w-full table-auto display text-left">
                    <thead>
                        <tr class="bg-gray-2 text-left dark:bg-gray-700">
                            <th class="min-w-[150px] py-4 px-4 font-medium text-black dark:text-white">Name</th>
                            <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">Position</th>
                            <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">Office</th>
                            <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">Age</th>
                            <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">Start date</th>
                            <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-gray-700 xl:pl-1"><h5 class="font-medium text-black dark:text-white">Tiger Nixon</h5></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">System Architect</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">Edinburgh</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">61</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">2011/04/25</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">$320,800</p></td>
                        </tr>
                        <tr>
                            <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-gray-700 xl:pl-1"><h5 class="font-medium text-black dark:text-white">Garrett Winters</h5></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">Accountant</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">Tokyo</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">63</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">2011/07/25</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">$170,750</p></td>
                        </tr>
                         <tr>
                            <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-gray-700 xl:pl-1"><h5 class="font-medium text-black dark:text-white">Ashton Cox</h5></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">Junior Technical Author</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">San Francisco</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">66</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">2009/01/12</p></td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-gray-700"><p class="text-black dark:text-white">$86,000</p></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.2.1/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.2.1/js/dataTables.tailwindcss.js"></script>
    <script>
        $(document).ready(function() {
            $('#exampleTable').DataTable();
        });
    </script>
@endpush
