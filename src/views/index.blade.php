<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Mail Log Viewer</title>
    <style>
        /* Reset margin, padding, and box-sizing for all elements */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Define CSS variables for theme colors */
        :root {
            --primary-color: {{ config('laravel-mail-log-viewer.primary-color', '#ff2d20')}};
            --secondary-color: #2ecc71;
            --background-color: #f9f9f9;
            --text-color: #333;
            --border-color: #ddd;
            --nav-color: #333;
            --footer-color: #333;
        }

        /* Style for the body element */
        body {
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Style for the navigation bar */
        .lmv-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--nav-color);
            color: white;
            padding: 10px 20px;
            height: 60px;
            z-index: 1000;
        }

        /* Style for the left side of the navigation bar */
        .lmv-left-nav {
            display: flex;
            align-items: center;
        }

        /* Style for the logo in the navigation bar */
        .lmv-logo {
            font-size: 20px;
            font-weight: bold;
            margin-right: 15px;
            color: var(--primary-color);
        }

        /* Style for the dashboard link in the navigation bar */
        .lmv-dashboard-link {
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        /* Style for the main mail viewer container */
        .lmv-mail-viewer {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 180px);
            margin-top: 60px;
            flex-grow: 1;
        }

        /* Style for the email list container */
        .lmv-email-list {
            width: 100%;
            background-color: white;
            border-bottom: 1px solid var(--border-color);
            overflow-y: auto;
            max-height: 30vh;
        }

        /* Style for individual email items in the list */
        .lmv-email-item {
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        /* Hover effect for email items */
        .lmv-email-item:hover {
            background-color: #f0f0f0;
        }

        /* Style for selected email item */
        .lmv-email-item.selected {
            background-color: #e8f0fe;
            border-left: 4px solid var(--primary-color);
        }

        /* Style for email item headers */
        .lmv-email-item h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }

        /* Style for email item paragraphs */
        .lmv-email-item p {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #666;
        }

        /* Style for email item spans */
        .lmv-email-item span {
            font-size: 12px;
            color: #999;
        }

        /* Style for the email content container */
        .lmv-email-content {
            flex-grow: 1;
            padding: 20px;
            background-color: white;
            overflow-y: auto;
        }

        /* Style for the email header in the content container */
        .lmv-email-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Style for the email header title */
        .lmv-email-header h2 {
            margin: 0 10px 0 0;
            font-size: 20px;
        }

        /* Style for the email header paragraphs */
        .lmv-email-header p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        /* Style for the email body in the content container */
        .lmv-email-body {
            font-size: 16px;
            line-height: 1.6;
        }

        /* Media query for larger screens */
        @media (min-width: 768px) {
            .lmv-mail-viewer {
                flex-direction: row;
            }

            .lmv-email-list {
                width: 400px;
                max-height: none;
                border-right: 1px solid var(--border-color);
                border-bottom: none;
            }

            .lmv-email-content {
                flex-grow: 1;
            }
        }

        /* Media query for smaller screens */
        @media (max-width: 600px) {
            .lmv-navbar {
                flex-direction: column;
                height: auto;
                padding: 10px;
            }

            .lmv-left-nav {
                margin-bottom: 10px;
            }

            .lmv-nav-links li {
                margin: 5px 10px;
            }

            .lmv-mail-viewer {
                margin-top: 100px;
            }
        }

        /* Media query for pagination on smaller screens */
        @media (max-width: 600px) {
            .lmv-pagination {
                flex-wrap: wrap;
            }

            #lmv-currentPage {
                width: 100%;
                text-align: center;
                margin-bottom: 10px;
            }
        }

        /* Style for the footer */
        .lmv-footer {
            background-color: var(--footer-color);
            color: white;
            text-align: center;
            padding: 10px 0;
        }

        /* Style for links in the footer */
        .lmv-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }

        /* Style for the "no emails found" message */
        .no-emails-found {
            text-align: center;
            font-size: 24px;
            color: var(--primary-color);
            margin-top: 20px;
        }
    </style>
</head>
<body>
<nav class="lmv-navbar">
    <div class="lmv-left-nav">
        <div class="lmv-logo">Laravel Mail Log Viewer</div>
        <a href="{{config('laravel-mail-log-viewer.dashboard_url','/home')}}" class="lmv-dashboard-link">Dashboard</a>
    </div>
</nav>
<div class="lmv-mail-viewer">
    <div class="lmv-email-list">
        @forelse($emails as $key=>$email)
            <div class="lmv-email-item {{$key==0 ? 'selected':''}}"
                 data-subject="{{ $email['headers']['Subject'] }}"
                 data-from="{{ $email['headers']['From'] }}"
                 data-to="{{ $email['headers']['To'] }}"
                 data-date="{{ \Carbon\Carbon::parse($email['headers']['Date'])->format('D, d M Y h:i:s A') }}"
                 data-body="{{ $email['body'] }}">
                <h3>Subject: {{\Illuminate\Support\Str::limit($email['headers']['Subject'],20)}}</h3>
                <p>From: {{$email['headers']['From']}}</p>
                <p>To: {{$email['headers']['To']}}</p>
                <span>{{\Carbon\Carbon::parse($email['headers']['Date'])->format('D, d M Y h:i:s A')}}</span>
            </div>
        @empty
            <h2 class="no-emails-found">No emails found</h2>
        @endforelse
        <div class="lmv-pagination">
            {{ $emails->links('emaillogviewer::pagination') }}
        </div>
    </div>
    @php($emails = $emails->items())
    @if(count($emails) > 0)
        <div class="lmv-email-content">
            <div class="lmv-email-header">
                <div>
                    <h2 id="email-subject">{{ $emails[array_key_first($emails)]['headers']['Subject'] }}</h2>
                    <p id="email-from">From: {{ $emails[array_key_first($emails)]['headers']['From'] }}</p>
                    <p id="email-to">To: {{ $emails[array_key_first($emails)]['headers']['To'] }}</p>
                    <p id="email-date">
                        Date: {{ \Carbon\Carbon::parse($emails[array_key_first($emails)]['headers']['Date'])->format('D, d M Y h:i:s A') }}</p>
                </div>
            </div>
            <div class="lmv-email-body" id="email-body">
                <p>{!! $emails[array_key_first($emails)]['body'] !!}</p>
            </div>
        </div>
    @else
        <div class="lmv-email-content">
            <h2 class="no-emails-found">No emails found</h2>
        </div>
    @endif

</div>
<footer class="lmv-footer">
    <p>&copy; {{ date('Y') }} <a href="https://github.com/Dipesh79/LaravelMailLogViewer"
                                                  target="_blank">Laravel Mail Log Viewer.</a></p>
</footer>
<script>
    // Add click event listeners to all email items
    document.querySelectorAll('.lmv-email-item').forEach(item => {
        item.addEventListener('click', function () {
            // Remove 'selected' class from all items
            document.querySelectorAll('.lmv-email-item').forEach(el => {
                el.classList.remove('selected');
            });

            // Add 'selected' class to clicked item
            this.classList.add('selected');

            // Update email content
            document.getElementById('email-subject').innerText = this.dataset.subject;
            document.getElementById('email-from').innerText = 'From: ' + this.dataset.from;
            document.getElementById('email-to').innerText = 'To: ' + this.dataset.to;
            document.getElementById('email-date').innerText = 'Date: ' + this.dataset.date;
            document.getElementById('email-body').innerHTML = this.dataset.body;

            // Add target="_blank" to all links in the email body
            updateLinksToOpenInNewTab();
        });
    });

    /**
     * Function to select the first email item if none are selected
     */
    function selectFirstEmailItem() {
        const firstEmailItem = document.querySelector('.lmv-email-item');
        if (firstEmailItem && !document.querySelector('.lmv-email-item.selected')) {
            firstEmailItem.classList.add('selected');
            document.getElementById('email-subject').innerText = firstEmailItem.dataset.subject;
            document.getElementById('email-from').innerText = 'From: ' + firstEmailItem.dataset.from;
            document.getElementById('email-to').innerText = 'To: ' + firstEmailItem.dataset.to;
            document.getElementById('email-date').innerText = 'Date: ' + firstEmailItem.dataset.date;
            document.getElementById('email-body').innerHTML = firstEmailItem.dataset.body;
        }
    }

    /**
     * Function to add target="_blank" to all links in the email body
     */
    function updateLinksToOpenInNewTab() {
        document.querySelectorAll('#email-body a').forEach(link => {
            link.setAttribute('target', '_blank');
        });
    }

    // Call the function when the page loads
    window.onload = selectFirstEmailItem;
    // Add target="_blank" to all links in the email body
    updateLinksToOpenInNewTab();
</script>
</body>
</html>
