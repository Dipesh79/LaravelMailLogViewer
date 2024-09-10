{{--
    Blade template for pagination controls.

    This template checks if the paginator has pages and displays
    pagination buttons and the current page information accordingly.
--}}

@if ($paginator->hasPages())
    <nav class="lmv-pagination">
        {{-- Check if the paginator is on the first page --}}
        @if ($paginator->onFirstPage())
            {{-- Disable the "First" and "Previous" buttons if on the first page --}}
            <button class="lmv-pagination-btn" disabled><<</button>
            <button class="lmv-pagination-btn" disabled>Previous</button>
        @else
            {{-- Enable the "First" and "Previous" buttons and set the onclick event to navigate to the first and previous pages --}}
            <button class="lmv-pagination-btn" onclick="window.location='{{ $paginator->url(1) }}'"><<</button>
            <button class="lmv-pagination-btn" onclick="window.location='{{ $paginator->previousPageUrl() }}'">Previous</button>
        @endif

        {{-- Display the current page and total pages --}}
        <span id="lmv-currentPage">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        {{-- Check if the paginator has more pages --}}
        @if ($paginator->hasMorePages())
            {{-- Enable the "Next" and "Last" buttons and set the onclick event to navigate to the next and last pages --}}
            <button class="lmv-pagination-btn" onclick="window.location='{{ $paginator->nextPageUrl() }}'">Next</button>
            <button class="lmv-pagination-btn" onclick="window.location='{{ $paginator->url($paginator->lastPage()) }}'">>></button>
        @else
            {{-- Disable the "Next" and "Last" buttons if there are no more pages --}}
            <button class="lmv-pagination-btn" disabled>Next</button>
            <button class="lmv-pagination-btn" disabled>>></button>
        @endif
    </nav>
@endif

{{--
    CSS styles for the pagination controls.

    .lmv-pagination: Styles the pagination container.
    .lmv-pagination-btn: Styles the pagination buttons.
    .lmv-pagination-btn:disabled: Styles the disabled pagination buttons.
    #lmv-currentPage: Styles the current page information.
--}}
<style>
    .lmv-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
        background-color: #fff;
    }

    .lmv-pagination-btn {
        background-color: var(--primary-color);
        color: white;
        border: none;
        padding: 5px 10px;
        margin: 0 5px;
        cursor: pointer;
        font-size: 14px;
    }

    .lmv-pagination-btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
    }

    #lmv-currentPage {
        margin: 0 10px;
        font-size: 14px;
    }
</style>
