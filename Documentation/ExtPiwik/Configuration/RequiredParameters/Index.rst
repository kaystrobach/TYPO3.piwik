

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


Required parameters
^^^^^^^^^^^^^^^^^^^

Defines your Piwik site id and host/path to your piwik installation.
This parameter **needs to be set** for the extension to work. If your
Sites ID is 3 and the path your piwik installation is
stats.myhost.rl/piwik/piwik.php, your TypoScript in the template
should look like:

::

   config.tx_piwik {
         piwik_idsite = 3
           piwik_host   = http://stats.myhost.rl/piwik/
   }

Once you set up this parameter, you can check if everything worked
correctly by looking at your pages HTML source. The piece of code that
drives Piwik is inserted right before the closing tag of <body>
container:

::

   <!-- Start B-Net1 Piwik Tag →
   <script language="javascript" src="http://stats.myhost.rl/piwik/piwik.js" type="text/javascript"></script>
   <script type="text/javascript">
   <!--
   try {
   var piwikTracker = Piwik.getTracker("http://stats.myhost.rl/piwik/piwik.php", 3);
   piwikTracker.enableLinkTracking();
   piwikTracker.trackPageView();
   } catch( err ) {}
   //-->
   </script>
   <noscript>
   <p><img src="http://stats.myhost.rl/piwik/piwik.php?idsite=3" style="border:0" alt="piwik"/></p>
   </noscript>
   <!-- End B-Net1 Piwik Tag →
   </body>

