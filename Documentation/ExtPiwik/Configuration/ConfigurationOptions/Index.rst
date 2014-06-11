

.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. ==================================================
.. DEFINE SOME TEXTROLES
.. --------------------------------------------------
.. role::   underline
.. role::   typoscript(code)
.. role::   ts(typoscript)
   :class:  typoscript
.. role::   php(code)


Configuration Options
^^^^^^^^^^^^^^^^^^^^^

The following table shows you all configuration options for the Piwik
JavaScript API. All these parameters you have to set in your
TypoScript Template like the required parameters piwik\_idsite and
piwik\_host.

.. ### BEGIN~OF~TABLE ###

.. container:: table-row

   Property
         Property:
   
   Data type
         Data type:
   
   Description
         Description:
   
   Default
         Default:


.. container:: table-row

   Property
         piwik\_idsite
   
   Data type
         string
   
   Description
         The id of your Piwik account
   
   Default


.. container:: table-row

   Property
         piwik\_host
   
   Data type
         string
   
   Description
         The host / path to your piwik installation without URL-scheme
   
   Default


.. container:: table-row

   Property
         actionName
   
   Data type
         string / stdWrap
   
   Description
         This parameter controll the action name, which will be tracked by
         Piwik. This parameter has one special keyword:
         
         “TYPO3” this means, that the page title will be used for this
         parameter.
         
         All other values will be rendered directly to this JavaScript
         variable. If you want to use other JavaScript objects like
         :code:`document.title` you can do so. If you want to overwrite the
         parameter with an static string, like from TS, you have to quote the
         value with single quotes.
         
         **This extension will not quote the value of this parameter.**
   
   Default
         Empty string


.. container:: table-row

   Property
         trackGoal
   
   Data type
         int
   
   Description
         ID of the goal to be triggered
   
   Default


.. container:: table-row

   Property
         setDownloadExtensions
   
   Data type
         string
   
   Description
         A list of file extensions, divided by a pipe symbol (\|).
   
   Default
         :code:`7z\|aac\|avi\|csv\|doc\|exe\|flv\|gif\|gz\|jpe?g\|js\|mp(3\|4\|
         e?g)\|mov\|pdf\|phps\|png\|ppt\|rar\|sit\|tar\|torrent\|txt\|wma\|wmv\
         |xls\|xml\|zip`


.. container:: table-row

   Property
         addDownloadExtensions
   
   Data type
         string
   
   Description
         A list of file extensions, divided by a pipe symbol (\|).
   
   Default
         :code:`7z\|aac\|avi\|csv\|doc\|exe\|flv\|gif\|gz\|jpe?g\|js\|mp(3\|4\|
         e?g)\|mov\|pdf\|phps\|png\|ppt\|rar\|sit\|tar\|torrent\|txt\|wma\|wmv\
         |xls\|xml\|zip`


.. container:: table-row

   Property
         setDomains
   
   Data type
         string / list
   
   Description
         A comma separated list of host aliases for your site.
         
         By default all links to domains other than the current domain are
         considered outlinks. If you have multiple domains and don’t want to
         consider links to these websites as “outlinks” you can add this new
         javascript variable.
   
   Default


.. container:: table-row

   Property
         setLinkTrackingTimer
   
   Data type
         int
   
   Description
         When a user clicks to download a file, or when he clicks on an
         outbound link, Piwik records it: it adds a small delay before the user
         is redirected to the requested file or link. We use a default value of
         500ms, but you can set it shorter, with the risk that this time is not
         long enough to record the data in Piwik.
   
   Default
         500


.. container:: table-row

   Property
         enableLinkTracking
   
   Data type
         boolean
   
   Description
         To disable all the automatic downloads and outlinks tracking, you must
         set this parameter to 0
   
   Default
         1


.. container:: table-row

   Property
         setIgnoreClasses
   
   Data type
         string
   
   Description
         You can disable automatic download and outlink tracking for links with
         this CSS classes.
   
   Default


.. container:: table-row

   Property
         setDownloadClasses
   
   Data type
         string
   
   Description
         If you want Piwik to consider a given link as a download, you can add
         the 'piwik\_download' css class to the link.
         
         With this parameter you can customize and rename the CSS class used to
         force a click to being recorded as a download
   
   Default


.. container:: table-row

   Property
         setLinkClasses
   
   Data type
         string
   
   Description
         If you want Piwik to consider a given link as an outlink (links to the
         current domain or to one of the alias domains), you can add the
         'piwik\_link' css class to the link.
         
         With this parameter you can customize and rename the CSS class used to
         force a click to being recorded as an outlink.
   
   Default


.. ###### END~OF~TABLE ######

[TS-Setup: config.tx\_bn1piwik]

