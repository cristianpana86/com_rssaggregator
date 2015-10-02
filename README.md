# com_rssaggregator
Joomla Component - generates articles from RSS feeds


How to use it:

1. Download as zip from GitHub
2. Install the component from your admin panel
3. In your administrator dashboard look for RSS Aggregator ->Sources view
4. Add at least one valid RSS feed
5. Run cron.rssaggregator.com (if you allow direct access to your files which is very bad practice, you write in the browser the path to your file, something like this: http:///administrator/components/com_rssaggregator/cron.rssaggregator.php). Otherwise you can use a command line and execute it as any php file.
6. Add the script in cron to be executed recursively, depending on how often the content of the RSS is refreshed.
