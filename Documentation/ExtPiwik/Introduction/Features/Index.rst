

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


Features
^^^^^^^^

- Implements the Javascript Tracking Code for the new Piwik Javascript
  Tracking API that is included since Piwik 0.4

- The following Piwik Tracking Functions can be configured with
  Typoscript.
  
  - piwikTracker.setDocumentTitle()
  
  - piwikTracker.trackGoal()
  
  - piwikTracker.setDomains()
  
  - piwikTracker.enableLinkTracking()
  
  - piwikTracker.setIgnoreClasses()
  
  - piwikTracker.setDownloadClasses()
  
  - piwikTracker.setLinkClasses()
  
  - piwikTracker.setLinkTrackingTimer()
  
  - piwikTracker.setDownloadExtensions()
  
  - piwikTracker.addDownloadExtensions()

- If You are logged in as a BE-User your FE-Hits will not be tracked.

