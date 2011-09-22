Google Reader migration between Google accounts
===============================================

This script (when finished) will be used to migrate ALL of my Google Reader
settings from current account I use for Reader (@gmail.com) to my actual
Google Apps account (@laacz.lv).

What I am able to mgirate now
-----------------------------

* Subscriptions
* Subscription labels (tags, folders)
* Starred items
* Shared items with annotations (notes, if they have one)
* Read and unread items [1]
* Friends I follow [2][3]

[1] Read items migration is a little shaky. Although item is being marked as unread,
    it does not work. Also - items lose feature (in web interface, at least) of
    being marked unread at all.

[2] Friends are being added, but Google Reader goes nuts about that. All freshly
    added friends ar anonymouses, and have mystical ID's. [3]
    
[3] I just noticed that all migrated friends are now proper people, not Anomymouses. It
    over last days, I believe. If someone is going to try migration, give a feedback
    (send an e-mail, post an Issue, or something), if friends migration works now.

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
