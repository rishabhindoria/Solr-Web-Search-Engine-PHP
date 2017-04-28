•	Created a file big.txt using apache Tika from all html files indexed in solr on which the user will issue queries.

•	This big.txt contains all the keywords used for spelling corrections.

•	Provided big.txt file as an input to Norvig SpellCorrector.php program.

•	Passed the query words entered by user to SpellCorrector.php program and in the case of conflicts displayed the message of "Showing results for X instead of Y" where Y was user query and X was returned result by SpellCorrector.php program.

•	Used default AJAX autocomplete functionality for autocomplete-Called proxy.php file which fetched JSON results from solr, returning 5 suggestions for a user entered query.

•	Converted html page data to text for snippet generation using simple_html_dom.php.

•	Text data was then split on full stops and later based on individual query terms for getting first match sentence for the user query.
