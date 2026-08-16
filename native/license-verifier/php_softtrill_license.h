#ifndef PHP_SOFTTRILL_LICENSE_H
#define PHP_SOFTTRILL_LICENSE_H

extern zend_module_entry softtrill_license_module_entry;
#define phpext_softtrill_license_ptr &softtrill_license_module_entry

#define PHP_SOFTTRILL_LICENSE_VERSION "1.0.0"
#define PHP_SOFTTRILL_LICENSE_EXTNAME "softtrill_license"

#if defined(ZTS) && defined(COMPILE_DL_SOFTTRILL_LICENSE)
ZEND_TSRMLS_CACHE_EXTERN()
#endif

#endif /* PHP_SOFTTRILL_LICENSE_H */
