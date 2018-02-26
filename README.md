# LimeSurvey-ShibbolethAuth

**LimeSurvey 3.4+ Shibboleth auth plugin**

**LimeSurvey: http://www.limesurvey.org/**

## PREREQUISITES
* Running installation of LimeSurvey 3.4+
* libapache2-mod-shib2 -> Running Shibboleth SP
* git

## INSTALL PLUGIN

In the following example the LimeSurvey working directory is /var/www/limesurvey

To install this plugin you have to create a folder "ShibbolethAuth" into folder /plugins/ of your LimeSurvey installation and copy into that folder the file "ShibbolethAuth.php":

```bash
cd /var/www/limesurvey/plugins
mkdir ShibbolethAuth
cd ShibbolethAuth
git clone https://github.com/atlet/LimeSurvey-ShibbolethAuth.git .
```
## ACTIVATE PLUGIN FROM ADMIN PANEL

Now you can activate and configure the new installed plugin

## CONFIGURE APACHE2 FOR SHIBBOLETH AUTHENTICATION

You have two alternatives: 

**Protect frontend and admin panel with Shibboleth**
To protect frontend and admin panel you can add the following to apache2 configuration:
```bash
   <Location />
             AuthType shibboleth
             ShibRequireSession On
             require valid-user
   </Location>
```

**Protect only admin panel with Shibboleth**
With this method you will be able to protect only the admin panel, add the following to apache2 configuration:
```bash
   <Location /admin>
             AuthType shibboleth
             ShibRequireSession On
             require valid-user
   </Location>
   
   <Location />
             AuthType shibboleth
             ShibRequestSetting requireSession false
             ShibUseHeaders On
             Require shibboleth
  </Location>
```
