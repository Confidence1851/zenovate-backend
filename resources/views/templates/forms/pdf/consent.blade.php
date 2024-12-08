<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Consent Form</title>
    <style>
        body {
            font-size: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 5px;
        }

        .no_border {
            border: none !important;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
        }

        .p_5 {
            padding: 5px;
        }

        .hr {
            border: 1px solid #000;
            margin: 15px 0;
        }
    </style>
</head>

<body>
    <h1>Zenovate - Consent Form</h1>
    <hr class="hr">

    <h2 class="section-title">Acknowledgment and Consent</h2>

    <table>
        <tbody>
            <tr>
                <td><b>Full Name:</b></td>
                <td>{{ $dto->fullName() }}</td>
            </tr>
            <tr>
                <td><b>Date of Birth (MM/DD/YYYY):</b></td>
                <td>{{ $dto->dob2() }}</td>
            </tr>
            <tr>
                <td><b>Address:</b></td>
                <td>{{ $dto->streetAddress() }}</td>
            </tr>
            <tr>
                <td><b>Phone Number:</b></td>
                <td>{{ $dto->phone() }}</td>
            </tr>
            <tr>
                <td><b>Email Address:</b></td>
                <td>{{ $dto->email() }}</td>
            </tr>
        </tbody>
    </table>
    <hr class="hr">

    <p>By signing this form online, I, the undersigned, confirm the following:</p>
    <h5 class="section-title">Understanding of the Product and Procedure</h5>
    <p>I acknowledge that I am purchasing subcutaneous injectable products from Zenovate.</p>
    <p>
        I have reviewed the information provided about the product, including potential benefits, risks,
        and proper administration techniques.
    </p>
    <p>
        I understand that Zenovate’s products are for <b>wellness purposes only</b>, not for therapeutic or
        diagnostic purposes. Zenovate makes no claims regarding the effectiveness of its products.
    </p>
    <hr class="hr">


    <h5 class="section-title">Educational Resources for Administration</h5>
    <p>
        I understand that proper administration of subcutaneous injections is critical to safety and efficacy.
    </p>
    <p>
        I confirm that I have reviewed the instructional materials provided by Zenovate:
    </p>
    <ul>
        <li>Video Guide: <a href="#" target="_blank">Click Here</a></li>
        <li>Detailed Document: <a href="#" target="_blank">Click Here</a></li>
    </ul>
    <hr class="hr">


    <h5 class="section-title">Medical Supervision and Disclosure of Co-Morbidities</h5>
    <p>
        I confirm that my medical history has been reviewed by a licensed medical practitioner affiliated
        with Zenovate.
    </p>
    <p>
        I have disclosed any existing medical conditions, including chronic illnesses, allergies, or other
        co-morbidities, to the medical practitioner.
    </p>
    <p>
        I understand that failure to disclose such information could increase the risk of adverse
        outcomes and release Zenovate from liability associated with undisclosed conditions.
    </p>
    <hr class="hr">


    <h5 class="section-title">Risks and Side Effects</h5>
    <p>
        I understand that all medications, including injectable vitamins, may have potential risks and
        side effects, including but not limited to:
    </p>
    <p>
        <b>Common Risks:</b>
    </p>
    <ul>
        <li>Discomfort, pain, or bruising at the injection site.</li>
        <li>Redness or swelling at the injection area.</li>
        <li>Minor bleeding at the injection site.</li>
    </ul>

    <p>
        <b>Less Common Risks:</b>
    </p>
    <ul>
        <li>Infection at the injection site.</li>
        <li>Allergic reactions, which may include rash, itching, or more severe responses.</li>
        <li>Formation of lumps or nodules under the skin.</li>
        <li>Nerve injury leading to temporary or permanent numbness or weakness.</li>
        <li>Scarring or skin discoloration at the injection site.</li>
        <li>Inflammation of the vein used for injection (phlebitis).</li>
        <li>Metabolic disturbances.</li>
        <li>Injury to surrounding tissues.</li>
    </ul>
    <hr class="hr">



    <h5 class="section-title">Limitations of Use</h5>
    <p>
        I understand that these products are intended solely for <b>personal wellness management</b> and
        should not replace professional medical care for specific health conditions.
    </p>
    <p>
        I agree not to use these products as a treatment for any illness or medical condition unless
        advised by a licensed healthcare provider.
    </p>
    <hr class="hr">


    <h5 class="section-title">Usage and Responsibility</h5>
    <p>
        I agree to follow all instructions provided with the product and any advice given by Zenovate’s
        medical team.
    </p>
    <p>
        I acknowledge that I am solely responsible for properly storing, handling, and using the product.
    </p>
    <p>
        I will not share my prescription or Zenovate products with anyone else.
    </p>
    <hr class="hr">

    <h5 class="section-title">Emergency Situations</h5>
    <p>
        I understand that in the event of a severe adverse reaction, such as difficulty breathing, swelling
        of the face or throat, or other signs of anaphylaxis, I should seek immediate medical attention or
        call emergency services.
    </p>
    <hr class="hr">


    <h5 class="section-title">Release of Liability</h5>
    <p>
        I release Zenovate, its affiliated pharmacies, healthcare providers, and employees from any
        liability for adverse effects or outcomes that may arise from the use or misuse of the products.
    </p>
    <p>
        I acknowledge that Zenovate is not liable for issues related to third-party services, including
        shipping, fulfillment, or any complications arising from unauthorized product use.
    </p>
    <hr class="hr">


    <h5 class="section-title">Privacy and Confidentiality</h5>
    <p>
        I understand that my personal and medical information will be kept confidential, except where
        required by law or to fulfill my order with a licensed pharmacy.
    </p>
    <hr class="hr">


    <h5 class="section-title">Refund and Return Policy</h5>
    <p>
        I acknowledge that Zenovate does not offer refunds for opened or used products, and all refund
        requests will be reviewed according to company policies.
    </p>
    <hr class="hr">


    <h5 class="section-title">Voluntary Consent</h5>
    <p>
        I affirm that I am at least 18 years old and am providing this consent voluntarily, without any
        coercion or undue influence.
    </p>
    <p>
        I understand that I may withdraw my consent at any time but acknowledge that doing so may
        affect my ability to continue receiving products from Zenovate.
    </p>
    <hr class="hr">


    <h5 class="section-title">Signature</h5>
    <p>
        By signing below, I confirm that I have read, understood, and agree to the terms outlined in this
        consent form.
    </p>
    <table class="no_border">
        <tbody>
            <tr>
                <td class="p_5"><b>Electronic Signature:</b></td>
                <td class="p_5"> <i>{{ $dto->fullName() }}</i> </td>
            </tr>
            <tr>
                <td class="p_5"><b>Printed Name:</b></td>
                <td class="p_5">{{ $dto->fullName() }}</td>
            </tr>
            <tr>
                <td class="p_5"><b>Date (MM/DD/YYYY):</b></td>
                <td class="p_5">{{ $dto->signatureDate() }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
