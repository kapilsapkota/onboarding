<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default project title shown on the cover page (page 1)
    |--------------------------------------------------------------------------
    | Used when $quote->project_title is null.
    */
    'default_project_title' => 'Digital Transformation',

    /*
    |--------------------------------------------------------------------------
    | Fixed cover image (page 1 background)
    |--------------------------------------------------------------------------
    | Path is relative to /public. Place your image at public/images/quotes/cover.jpg
    */
    'cover_image' => 'images/quotes/cover.jpg',

    /*
    |--------------------------------------------------------------------------
    | Company logo shown top-left of the cover page
    |--------------------------------------------------------------------------
    | Place your logo at public/images/company/logo.png
    */
    'company_logo' => 'images/company/logo.png',

    /*
    |--------------------------------------------------------------------------
    | Default partner logos (page 2)
    |--------------------------------------------------------------------------
    | Used whenever $quote->partner_logos is null. Each entry needs a name
    | (used as alt text) and a path relative to /public.
    | Override per-quote by setting $quote->partner_logos to a JSON array
    | in this same shape, e.g.:
    |   $quote->partner_logos = [['name' => 'Xero', 'logo' => 'images/partners/xero.png']];
    */
    'default_partner_logos' => [
        ['name' => 'Partner One', 'logo' => 'images/partners/partner-one.png'],
        ['name' => 'Partner Two', 'logo' => 'images/partners/partner-two.png'],
        ['name' => 'Partner Three', 'logo' => 'images/partners/partner-three.png'],
        ['name' => 'Partner Four', 'logo' => 'images/partners/partner-four.png'],
        ['name' => 'Partner Five', 'logo' => 'images/partners/partner-five.png'],
        ['name' => 'Partner Six', 'logo' => 'images/partners/partner-six.png'],
    ],
    'default_item_image' => 'images/default.webp',

    /*
    |--------------------------------------------------------------------------
    | Default terms & conditions (final page)
    |--------------------------------------------------------------------------
    | Used when $quote->terms_and_conditions is null. Plain text, paragraphs
    | separated by blank lines (rendered with nl2br in the view).
    */
    'default_terms' => [
        [
            'title' => 'Acceptance',
            'content' => 'It is not necessary for any Client to have signed an acceptance of these terms and conditions for them to apply. If a Client accepts a quote or a proposal from All in IT Solutions Pty Ltd then the Client will be deemed to have satisfied themselves as to the terms and conditions applying and have accepted these terms and conditions in full.

Please read these terms and conditions carefully. Any purchase or use of our services implies that you have read and accepted our terms and conditions.',
        ],

        [
            'title' => 'Payments & Charges',
            'content' => 'Charges for services and/or products provided by All in IT Solutions Pty Ltd are defined in the project quotation that the Client receives. Quotations are valid for a period of 30 days. All in IT Solutions Pty Ltd reserves the right to alter or decline a quotation after expiry of the 30 days.',
            'subsections' => [
                [
                    'title' => 'Deposit Payment',
                    'content' => 'All requested products and/or services require a deposit payment of a minimum of fifty (50) percent of the total project quotation before anything is supplied or work is commenced by All in IT Solutions Pty Ltd or provided to the Client for review.',
                ],
                [
                    'title' => 'Final Payment',
                    'content' => 'A second payment of fifty (50) percent of the total project quotation is required prior to uploading anything to the server or the release of any materials to the Client. Payment for products and/or services is due by way of EFT paid directly into our nominated bank account.',
                ],
            ],
        ],

        [
            'title' => 'Retention of Title',
            'content' => 'Notwithstanding the delivery of products by All in IT Solutions Pty Ltd to the Client, until the Client has affected full payment of the products and any other goods or services supplied by All in IT Solutions Pty Ltd:',
            'points' => [
                'Legal title to the products, goods or services will remain with All in IT Solutions Pty Ltd.',
                'The risk of the products, goods or services will pass to the Client on delivery to the Client or its agent.',
                'The relationship between the Client and All in IT Solutions Pty Ltd will be fiduciary.',
                'The Client will hold the products, goods or services as bailee for All in IT Solutions Pty Ltd.',
                'The Client will keep the products, goods or services separate from other goods and label them to be identifiable as being the property of All in IT Solutions Pty Ltd.',
            ],
        ],

        [
            'title' => 'Products and Goods Supplied',
            'content' => 'Unless specifically detailed in the body of the quotation the following items are excluded for all products and goods supplied by All in IT Solutions Pty Ltd:',
            'points' => [
                'The set-up, installation or configuration of the products or goods supplied.',
                'The delivery costs of the products or goods to the Client’s nominated delivery address.',
            ],
        ],

        [
            'title' => 'Client Review',
            'content' => 'All in IT Solutions Pty Ltd will provide the Client with an opportunity to review the appearance and content of the website during the design phase and once the overall website development is completed. At the completion of the project, such materials will be deemed accepted and approved unless the Client notifies All in IT Solutions Pty Ltd otherwise within seven (7) days of the date the materials are made available to the Client.',
        ],

        [
            'title' => 'Turnaround Time and Content Control',
            'content' => 'All in IT Solutions Pty Ltd will install and publicly post or supply the Client website by the date specified in the project proposal, or at a date agreed with the Client upon All in IT Solutions Pty Ltd receiving the initial payment, unless a delay is specifically requested by the Client and agreed in writing by All in IT Solutions Pty Ltd.

The Client agrees to delegate a single individual as a primary contact to aid All in IT Solutions Pty Ltd with progressing the commission in a satisfactory and expedient manner.',
        ],

        [
            'title' => 'Website Content',
            'content' => 'All in IT Solutions Pty Ltd must ensure that work programmed is carried out at the scheduled time. The Client must provide all required information in advance to enable All in IT Solutions Pty Ltd to complete work within the agreed timeframe.

If the Client fails to provide required information within one week of project commencement, All in IT Solutions Pty Ltd reserves the right to close the project and the balance remaining becomes immediately payable.

Text content should be delivered as a Microsoft Word document, email or similar format with pages representing the relevant pages of the website.',
        ],

        [
            'title' => 'Invoicing of Work & Products Supplied',
            'content' => 'Invoices will be provided by All in IT Solutions Pty Ltd for all products supplied and upon completion but before publishing the live website. Invoices are normally sent via email; however, the Client may choose to receive hard copy invoices. Invoices are due upon receipt.',
        ],
        [
            'title' => 'Additional Expenses',
            'content' => 'Client agrees to reimburse All in IT Solutions Pty Ltd for any additional expenses necessary for the completion of the work. Examples would be purchase of special fonts, stock photography etc. Additional expenses could also apply to necessary items required that are not specifically detailed in our proposal.',
        ],

        [
            'title' => 'Web Browsers',
            'content' => 'All in IT Solutions Pty Ltd makes every effort to ensure websites are designed to be viewed by the majority of visitors. Websites are designed to work with the most popular current browsers. Client agrees that All in IT Solutions Pty Ltd cannot guarantee correct functionality with all browser software across different operating systems.

All in IT Solutions Pty Ltd cannot accept responsibility for web pages which do not display acceptably in new versions of browsers released after the website has been designed and handed over to the Client. All in IT Solutions Pty Ltd reserves the right to quote for any work involved in changing the website design or website code for updated browser software.',
        ],

        [
            'title' => 'Payment Defaults',
            'content' => 'Accounts unpaid within 7 days after the date of invoice will be considered in default. If the Client in default maintains any information or files on All in IT Solutions Pty Ltd web space, All in IT Solutions Pty Ltd may remove all such material from its web space.

All in IT Solutions Pty Ltd is not responsible for any loss of data incurred due to removal of the service. Removal of such material does not relieve the Client of the obligation to pay outstanding charges.

Clients with accounts in default agree to pay All in IT Solutions Pty Ltd reasonable expenses, including legal fees and costs for collection by third-party agencies.',
        ],

        [
            'title' => 'Termination',
            'content' => 'Termination of services by the Client must be requested in writing and will be effective upon receipt of such notice. Verbal requests for termination will not be accepted.

The Client will be invoiced for all work completed or products supplied up to the date the written termination notice is received. Payment of such invoices must be made in full within 7 days.',
        ],

        [
            'title' => 'Indemnity',
            'content' => 'All in IT Solutions Pty Ltd services must be used for lawful purposes only. The Client agrees to fully indemnify and hold All in IT Solutions Pty Ltd harmless from any claims resulting from the Client’s use of our service that damages the Client or any other party.',
        ],

        [
            'title' => 'Copyright',
            'content' => 'The Client confirms that it owns and retains the copyright to the data, files and graphic logos provided by the Client, and grants All in IT Solutions Pty Ltd the rights to publish and use such material.

The Client must obtain permission and rights to use any information or files that are copyrighted by a third party. The Client agrees to indemnify and hold harmless All in IT Solutions Pty Ltd from any claims resulting from failure to obtain proper copyright permissions.

A contract for website design and/or placement shall be regarded as a guarantee by the Client that all permissions and authorities have been obtained.',
        ],

        [
            'title' => 'Standard Media Delivery',
            'content' => 'Unless otherwise specified in the project quotation, this Agreement assumes that any text will be provided by the Client in electronic format and that photographs and other graphics will be provided in suitable high-quality formats.

Although every reasonable attempt shall be made by All in IT Solutions Pty Ltd to return images or printed material provided for use in creation of the Client’s website, such return cannot be guaranteed.',
        ],

        [
            'title' => 'Design Credit',
            'content' => 'A link to All in IT Solutions Pty Ltd will appear in either small type or by a small graphic at the bottom of the Client’s website.',
        ],    [
            'title' => 'Access Requirements',
            'content' => 'If the Client’s website is to be installed on a third-party server, All in IT Solutions Pty Ltd must be granted temporary read/write access to the Client’s storage directories which must be accessible via FTP. Depending on the specific nature of the project, other resources might also need to be configured on the server.',
        ],

        [
            'title' => 'Post-Placement Alterations',
            'content' => 'All in IT Solutions Pty Ltd cannot accept responsibility for any alterations caused by a third party occurring to the Client’s pages once installed. Such alterations include, but are not limited to, additions, modifications or deletions.',
        ],

        [
            'title' => 'Domain Names',
            'content' => 'All in IT Solutions Pty Ltd may purchase domain names on behalf of the Client. Payment and renewal of those domain names is the responsibility of the Client.

The loss, cancellation or otherwise of the domain brought about by non-payment or late payment is not the responsibility of All in IT Solutions Pty Ltd. The Client must keep a record of due dates and ensure payment is made before expiry.',
        ],

        [
            'title' => 'General',
            'content' => 'These Terms and Conditions supersede all previous representations, understandings or agreements. The Client’s signature or payment of an advance/deposit fee constitutes agreement to and acceptance of these Terms and Conditions. Payment online is acceptance of our Terms and Conditions.',
        ],

        [
            'title' => 'Social Media Management',
            'content' => 'Social Media Marketing and Management is defined as helping a client promote their products or services through social media channels.

All in IT Solutions Pty Ltd will honour the components of the chosen social media package, providing an agreement to a minimum 3 month contract is served and monthly payments are received in advance.

In the event payment is not received on time, further work will be halted until payment is rectified.',
        ],

        [
            'title' => 'Governing Law',
            'content' => 'This Agreement shall be governed by the Law in force at the time in the state of NSW, Australia.',
        ],

        [
            'title' => 'Liability',
            'content' => 'All in IT Solutions Pty Ltd hereby excludes itself, its Employees and/or Agents from all and any liability from:',
            'points' => [
                'Loss or damage caused by any inaccuracy.',
                'Loss or damage caused by omission.',
                'Loss or damage caused by delay or error, whether the result of negligence or other cause in the production of the website.',
                'Loss or damage to client artwork/photos supplied for the site, whether the loss or damage results from negligence or otherwise.',
            ],
            'footer' => 'The entire liability of All in IT Solutions Pty Ltd to the Client in respect of any claim whatsoever or breach of this Agreement, whether or not arising out of negligence, shall be limited to the charges paid for the Services under this Agreement in respect of which the breach has arisen. All in IT Solutions Pty Ltd will not be held responsible for any consequential loss that may be suffered by the Client.',
        ],

        [
            'title' => 'Severability',
            'content' => 'In the event any one or more of the provisions of this Agreement shall be held to be invalid, illegal or unenforceable, the remaining provisions of this Agreement shall be unimpaired, and the Agreement shall not be void for this reason alone.

Such invalid, illegal or unenforceable provision shall be replaced by a mutually acceptable valid, legal and enforceable provision which comes closest to the intention of the parties underlying the invalidity.',
        ],
    ],
    'stage_columns' => [
        [
            'title' => 'Stage 1',
            'items' => [
                'Branding Implementation',
                'Web design & development',
                'Photo/Videoshoot',
                'Google my business optimisation',
                'Social media marketing',
                'IT Operations & Security overview',
            ],
        ],
        [
            'title' => 'Stage 2',
            'items' => [
                'Marketing strategy document',
                'IT Infrastructure review',
                'Email marketing campaigns',
                'Printed collateral',
                'Ordering system integration',
            ],
        ],
        [
            'title' => 'Stage 3',
            'items' => [
                'Execute online marketing strategy',
                'Traditional marketing options',
                'Automation overview IT implementation',
            ],
        ],
    ],
    'images' => [
        [
            'image' => 'images/media/image42.png',
            'placeholder' => 'Your Business Our Solutions',
        ],
        [
            'image' => 'images/media/image52.jpg',
            'placeholder' => 'About Us',
        ],
        [
            'image' => 'images/media/image46.jpg',
            'placeholder' => 'Our Team',
        ],
        [
            'image' => 'images/media/image62.jpg',
            'placeholder' => 'Team Profile',
        ],
        [
            'image' => 'images/media/image54.jpg',
            'placeholder' => 'Team Profile Continue',
        ],
        [
            'image' => 'images/media/image49.jpg',
            'placeholder' => 'Why Us',
        ],
        [
            'image' => 'images/media/image50.jpg',
            'placeholder' => 'Our 360 Services',
        ],
        [
            'image' => 'images/media/image53.jpg',
            'placeholder' => 'Branding and Graphic Design',
        ],
        [
            'image' => 'images/media/image48.jpg',
            'placeholder' => 'Website Design and Development',
        ],
        [
            'image' => 'images/media/image47.jpg',
            'placeholder' => 'Cloud Solutions',
        ],
        [
            'image' => 'images/media/image55.jpg',
            'placeholder' => 'Hosting Solutions',
        ],
        [
            'image' => 'images/media/image59.jpg',
            'placeholder' => 'Cyber Security',
        ],
        [
            'image' => 'images/media/image51.jpg',
            'placeholder' => 'Business Telephony and Data Services',
        ],
        [
            'image' => 'images/media/image56.jpg',
            'placeholder' => 'Hardware and Software',
        ],
        [
            'image' => 'images/media/image64.jpg',
            'placeholder' => 'Audio Visual',
        ],
        [
            'image' => 'images/media/image57.jpg',
            'placeholder' => 'Managed IT Solutions',
        ],
        [
            'image' => 'images/media/image58.jpg',
            'placeholder' => 'Photography and Videography',
        ],
        [
            'image' => 'images/media/image60.jpg',
            'placeholder' => 'Online Marketing',
        ],
        [
            'image' => 'images/media/image63.jpg',
            'placeholder' => 'Managed Print Solutions',
        ],
        [
            'image' => 'images/media/image65.jpg',
            'placeholder' => 'Our Clients',
        ],
        [
            'image' => 'images/media/image66.jpg',
            'placeholder' => 'Technology Partners',
        ],
    ],
    'gst_rate' => 0.10
];
