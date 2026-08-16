#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_softtrill_license.h"
#include "ext/standard/info.h"

/*
 * softtrill_verify_ed25519(string $message, string $signature, string $public_key): bool
 */
PHP_FUNCTION(softtrill_verify_ed25519)
{
    zend_string *message;
    zend_string *signature;
    zend_string *public_key;

    ZEND_PARSE_PARAMETERS_START(3, 3)
        Z_PARAM_STR(message)
        Z_PARAM_STR(signature)
        Z_PARAM_STR(public_key)
    ZEND_PARSE_PARAMETERS_END();

    // We will securely delegate to sodium_crypto_sign_verify_detached from within C.
    // This removes the check from plain-text PHP files, raising the barrier to entry for bypassing.
    zval fname, retval;
    zval params[3];

    ZVAL_STRING(&fname, "sodium_crypto_sign_verify_detached");
    
    ZVAL_STR(&params[0], signature);
    ZVAL_STR(&params[1], message);
    ZVAL_STR(&params[2], public_key);

    if (call_user_function(CG(function_table), NULL, &fname, &retval, 3, params) == SUCCESS) {
        if (Z_TYPE(retval) == IS_TRUE) {
            RETURN_TRUE;
        } else {
            RETURN_FALSE;
        }
    }

    zval_ptr_dtor(&fname);
    RETURN_FALSE;
}

/* Argument Info */
ZEND_BEGIN_ARG_INFO_EX(arginfo_softtrill_verify_ed25519, 0, 0, 3)
    ZEND_ARG_TYPE_INFO(0, message, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, signature, IS_STRING, 0)
    ZEND_ARG_TYPE_INFO(0, public_key, IS_STRING, 0)
ZEND_END_ARG_INFO()

/* Module functions */
static const zend_function_entry softtrill_license_functions[] = {
    PHP_FE(softtrill_verify_ed25519, arginfo_softtrill_verify_ed25519)
    PHP_FE_END
};

/* MINFO */
PHP_MINFO_FUNCTION(softtrill_license)
{
    php_info_print_table_start();
    php_info_print_table_header(2, "Softtrill License Native Verifier", "enabled");
    php_info_print_table_row(2, "Version", PHP_SOFTTRILL_LICENSE_VERSION);
    php_info_print_table_end();
}

/* Module entry */
zend_module_entry softtrill_license_module_entry = {
    STANDARD_MODULE_HEADER,
    PHP_SOFTTRILL_LICENSE_EXTNAME,
    softtrill_license_functions,
    NULL, /* MINIT */
    NULL, /* MSHUTDOWN */
    NULL, /* RINIT */
    NULL, /* RSHUTDOWN */
    PHP_MINFO_FUNCTION(softtrill_license),
    PHP_SOFTTRILL_LICENSE_VERSION,
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_SOFTTRILL_LICENSE
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(softtrill_license)
#endif
