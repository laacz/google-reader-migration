Google Reader migration between Google accounts
===============================================

This script can be used to migrate ALL of yout Google Reader subscription, tags items from 
one Google account to another. 

I used it to successfully migrate from my legacy @gmail.com account to my actual Google Apps 
account (@laacz.lv).

Important note
--------------

Since Google Reader has been stripped of previous social features, shared, liked items, and 
followers migration is no more possible.

How to use
----------

Download or clone. Run via PHP command-line: `php -q migrate.php`

What I am able to mgirate now
-----------------------------

* Subscriptions
* Subscription labels (tags, folders)
* Starred items
* Read and unread items [1]

[1] Read items migration is a little shaky. Although item is being marked as unread,
    it does not work. Also - items lose feature (in web interface, at least) of
    being marked unread at all.

What I will be able to migrate once finished
--------------------------------------------

* Add OAuth support to greader library

What won't be migrated?
-----------------------

### Entries having same tag as one or more feeds

That's because tags and labels share same notation in Google Reader. If we
label, let's say, feed with 'Good', all items, coming from this feed automatically
get tag 'Good'. So, by tagging any other feeds' entry with 'Good', you just
append that entry to bunch of others. If I would try to fetch all items, which
are labelled as 'Good', then migration could end up taking a year, if feeds
with that label contain lots of items.

Files
-----

Since filenames are not straightforward, I will try and carefully explain
meaning of each file.

@greader.php contains Google Reader API stuff.
@migrate.php contains migration script.

Links
-----

There is no single place for Google Reader API related information. API itself
is not official (not oficially released to public by Google).

### API itself

* http://www.google.com/reader/api

### Unofficial API documentation

* http://groups.google.com/group/fougrapi
* http://code.google.com/p/trystero/wiki/API
* http://www.consumedbycode.com/code/ci_google_reader
* http://blog.martindoms.com/2009/08/15/using-the-google-reader-api-part-1/
* http://blog.martindoms.com/2009/10/16/using-the-google-reader-api-part-2/
* http://blog.martindoms.com/2010/01/20/using-the-google-reader-api-part-3/

### Community

* http://code.google.com/p/pyrfeed/wiki/GoogleReaderAPI
* http://stackoverflow.com/
* http://quora.com/
