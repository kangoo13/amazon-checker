# -*- coding: utf-8 -*-
import scrapy


class AmazonChecker(scrapy.Spider):
    name = 'AmazonChecker'
    allowed_domains = ['amazon.fr']
    start_urls = ['https://www.amazon.fr/ap/signin?_encoding=UTF8&ignoreAuthState=1&openid.assoc_handle=frflex&openid.claimed_id=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select&openid.identity=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select&openid.mode=checkid_setup&openid.ns=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0&openid.ns.pape=http%3A%2F%2Fspecs.openid.net%2Fextensions%2Fpape%2F1.0&openid.pape.max_auth_age=0&openid.return_to=https%3A%2F%2Fwww.amazon.fr%2F%3Fref_%3Dnav_custrec_signin&switch_account=']

    def parse(self, response):
        if not self.username or not self.password:
            print ('pas bon')
        else:
            return scrapy.FormRequest.from_response(
                    response,
                    formdata={'username': self.username, 'password': self.password},
                    callback=self.after_login
                    )

    def after_login(self, response):
        print (response.body)
