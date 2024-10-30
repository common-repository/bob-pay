import { registerPaymentMethod } from "@woocommerce/blocks-registry";
import { decodeEntities } from "@wordpress/html-entities";
import { getSetting } from "@woocommerce/settings";

const settings = getSetting("bobpay_data", {});
const creditCardSettings = getSetting("bobpay_credit_card_data", {});
const scanToPaySettings = getSetting("bobpay_scan_to_pay_data", {});
const payShapSettings = getSetting("bobpay_pay_shap_data", {});
const capitecPaySettings = getSetting("bobpay_capitec_pay_data", {});
const instantEFTSettings = getSetting("bobpay_instant_eft_data", {});
const manualEFTSettings = getSetting("bobpay_manual_eft_data", {});
const defaultLabel = "Bob Pay";
const label = decodeEntities(settings.title) || defaultLabel;
const Content = () => {
	return decodeEntities(settings.description || "");
};

const BobPayLabel = () => {
	return (
		<span
			style={{
				display: "inline-flex",
				width: "80%",
				gridGap: "3px",
				alignItems: "center",
				flexWrap: "wrap",
			}}
		>
			{settings.title}&nbsp;
			<img
				style={{ height: "21px", margin: 0 }}
				src={settings.absa_url}
			/>
			<img style={{ height: "20px" }} src={settings.bank_zero_url} />
			<img style={{ height: "20px" }} src={settings.capitec_url} />
			<img style={{ height: "20px" }} src={settings.discovery_url} />
			<img style={{ height: "20px" }} src={settings.fnb_url} />
			<img style={{ height: "20px" }} src={settings.investec_url} />
			<img style={{ height: "20px" }} src={settings.nedbank_url} />
			<img style={{ height: "20px" }} src={settings.standard_bank_url} />
			<img style={{ height: "20px" }} src={settings.tyme_bank_url} />
		</span>
	);
};
const BobPay = {
	name: "bobpay",
	label: <BobPayLabel />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};
registerPaymentMethod(BobPay);

const BobPayCreditCardLabel = () => {
	return (
		<span
			style={{
				display: "inline-flex",
				width: "80%",
				gridGap: "3px",
				alignItems: "center",
				flexWrap: "wrap",
			}}
		>
			{creditCardSettings.title}&nbsp;
			<img style={{ height: "15px" }} src={creditCardSettings.visa_url} />
			<img
				style={{ height: "20px" }}
				src={creditCardSettings.mastercard_url}
			/>
		</span>
	);
};
const BobPayCreditCard = {
	name: "bobpay_credit_card",
	label: <BobPayCreditCardLabel />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};
registerPaymentMethod(BobPayCreditCard);

const BobPayScanToPayLabel = () => {
	return (
		<span
			style={{
				display: "inline-flex",
				width: "80%",
				gridGap: "3px",
				alignItems: "center",
				flexWrap: "wrap",
			}}
		>
			{scanToPaySettings.title}&nbsp;
			<img
				style={{ height: "20px" }}
				src={scanToPaySettings.scan_to_pay_url}
			/>
		</span>
	);
};
const BobPayScanToPay = {
	name: "bobpay_scan_to_pay",
	label: <BobPayScanToPayLabel />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: scanToPaySettings.supports,
	},
};
registerPaymentMethod(BobPayScanToPay);

const BobPayPayShapLabel = () => {
	return (
		<span
			style={{
				display: "inline-flex",
				width: "80%",
				gridGap: "3px",
				alignItems: "center",
				flexWrap: "wrap",
			}}
		>
			{payShapSettings.title}&nbsp;
			<img
				style={{ height: "20px" }}
				src={payShapSettings.pay_shap_url}
			/>
		</span>
	);
};
const BobPayPayShap = {
	name: "bobpay_pay_shap",
	label: <BobPayPayShapLabel />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: payShapSettings.supports,
	},
};
registerPaymentMethod(BobPayPayShap);

const BobPayCapitecPayLabel = () => {
	return (
		<span
			style={{
				display: "inline-flex",
				width: "80%",
				gridGap: "3px",
				alignItems: "center",
				flexWrap: "wrap",
			}}
		>
			{capitecPaySettings.title}&nbsp;
			<img
				style={{ height: "20px" }}
				src={capitecPaySettings.capitec_pay_url}
			/>
		</span>
	);
};
const BobPayCapitecPay = {
	name: "bobpay_capitec_pay",
	label: <BobPayCapitecPayLabel />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: capitecPaySettings.supports,
	},
};
registerPaymentMethod(BobPayCapitecPay);

const BobPayInstantEFTLabel = () => {
	return (
		<span
			style={{
				display: "inline-flex",
				width: "80%",
				gridGap: "3px",
				alignItems: "center",
				flexWrap: "wrap",
			}}
		>
			{instantEFTSettings.title}&nbsp;
			<img
				style={{ height: "21px", margin: 0 }}
				src={instantEFTSettings.absa_url}
			/>
			<img
				style={{ height: "20px" }}
				src={instantEFTSettings.bank_zero_url}
			/>
			<img
				style={{ height: "20px" }}
				src={instantEFTSettings.capitec_url}
			/>
			<img
				style={{ height: "20px" }}
				src={instantEFTSettings.discovery_url}
			/>
			<img style={{ height: "20px" }} src={instantEFTSettings.fnb_url} />
			<img
				style={{ height: "20px" }}
				src={instantEFTSettings.investec_url}
			/>
			<img
				style={{ height: "20px" }}
				src={instantEFTSettings.nedbank_url}
			/>
			<img
				style={{ height: "20px" }}
				src={instantEFTSettings.standard_bank_url}
			/>
			<img
				style={{ height: "20px" }}
				src={instantEFTSettings.tyme_bank_url}
			/>
		</span>
	);
};
const BobPayInstantEFT = {
	name: "bobpay_instant_eft",
	label: <BobPayInstantEFTLabel />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: instantEFTSettings.supports,
	},
};
registerPaymentMethod(BobPayInstantEFT);

const BobPayManualEFTLabel = () => {
	return (
		<span
			style={{
				display: "inline-flex",
				width: "80%",
				gridGap: "3px",
				alignItems: "center",
				flexWrap: "wrap",
			}}
		>
			{manualEFTSettings.title}&nbsp;
			<img
				style={{ height: "21px", margin: 0 }}
				src={manualEFTSettings.absa_url}
			/>
			<img
				style={{ height: "20px" }}
				src={manualEFTSettings.bank_zero_url}
			/>
			<img
				style={{ height: "20px" }}
				src={manualEFTSettings.capitec_url}
			/>
			<img
				style={{ height: "20px" }}
				src={manualEFTSettings.discovery_url}
			/>
			<img style={{ height: "20px" }} src={manualEFTSettings.fnb_url} />
			<img
				style={{ height: "20px" }}
				src={manualEFTSettings.investec_url}
			/>
			<img
				style={{ height: "20px" }}
				src={manualEFTSettings.nedbank_url}
			/>
			<img
				style={{ height: "20px" }}
				src={manualEFTSettings.standard_bank_url}
			/>
			<img
				style={{ height: "20px" }}
				src={manualEFTSettings.tyme_bank_url}
			/>
		</span>
	);
};
const BobPayManualEFT = {
	name: "bobpay_manual_eft",
	label: <BobPayManualEFTLabel />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: manualEFTSettings.supports,
	},
};
registerPaymentMethod(BobPayManualEFT);
