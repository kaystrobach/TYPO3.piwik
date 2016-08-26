

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


TypoScript Examples
^^^^^^^^^^^^^^^^^^^

Using the stdWrap feature of the “actionName” property, to build a
actionName hierarchy like a rootline navigation.

::

      config.tx_piwik {
                   piwik_idsite = 3
                   piwik_host   = http://stats.myhost.rl/piwik/
                   actionName= TYPO3
                   actionName {
                           stdWrap {
                                   cObject = HMENU
                                   cObject {
                                           special=rootline
                                           special.range= 1 | -1
                                           includeNotInMenu = 1
                                           wrap = |/index
                                           1=TMENU
                                           1.itemArrayProcFunc = user_UrteileItemArrayProcFunc
                                           1.NO.allWrap=  |   /   |*| |   /   |*| |
                                           1.NO.doNotLinkIt = 1
                                   }
                           }
                   }
           }

Custom User ID Examples
^^^^^^^^^^^^^^^^^^^^^^^

The User ID can be the Typo3 UID and/or the Username

::

      config.tx_piwik {
                   setUserId.data = TSFE:fe_user|user|uid
      }

