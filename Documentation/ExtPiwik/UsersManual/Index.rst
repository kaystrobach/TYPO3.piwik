

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


Users manual
------------

To install Piwik on your website follow these steps:

#. Install Piwik and create your Piwik account, for more information see
   `http://piwik.org/ <http://piwik.org/>`_

#. To enable Piwik tracking in your website, we need to know your site ID
   and hostname of the piwik installation.

#. Install the plugin from TYPO3 online repository and enable it

#. Add this configuration options to your template (both parameters are
   required):

::

   config.tx_piwik {
     piwik_idsite = (your site id)
     piwik_host   = (host/path of your piwik installation without URL-scheme)
   }

That's all you need to start tracking your visitors. See
“Configuration” section for more config options and description of the
parameters.


