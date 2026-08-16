PHP_ARG_ENABLE(softtrill_license, whether to enable softtrill_license support,
[  --enable-softtrill_license   Enable softtrill_license support])

if test "$PHP_SOFTTRILL_LICENSE" != "no"; then
  PHP_NEW_EXTENSION(softtrill_license, softtrill_license.c, $ext_shared)
fi
