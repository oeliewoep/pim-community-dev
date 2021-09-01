import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import {render, screen, waitForElement} from '@testing-library/react';
import {Permissions} from '@src/connect/components/AppWizardWithSteps/Permissions';

test('The permissions step renders without error', async () => {
    render(<Permissions />);
    await waitForElement(() => screen.getByTestId('permissions-step'));
    expect(screen.getByText('Hello permissions!')).toBeInTheDocument();
});
